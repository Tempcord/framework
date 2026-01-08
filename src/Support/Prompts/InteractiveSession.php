<?php

namespace Tempcord\Support\Prompts;

use Ragnarok\Fenrir\Discord;
use React\EventLoop\LoopInterface;
use React\Stream\ReadableResourceStream;
use Tempcord\Support\InteractiveModeService;
use Tempcord\Tempcord;
use Tempest\Console\Console;
use Tempest\Console\Key;
use Tempest\Console\Point;
use Tempest\Console\Terminal\Terminal;
use Tempest\Log\Logger;

final class InteractiveSession
{
    private ?Terminal $terminal = null;
    private bool $inCompletionMode = false;
    private bool $isCyclingMode = false;
    private array $currentCompletions = [];
    private int $selectedCompletionIndex = 0;
    private string $completionPrefix = '';
    private int $completionDisplayLines = 0;
    private float $lastEscapeTime = 0;
    private ?Point $inputLinePosition = null;

    public function __construct(
        private readonly PromptsBucket          $bucket,
        private readonly Console                $console,
        private readonly Tempcord               $tempcord,
        private readonly Logger                 $logger,
        private readonly InteractiveModeService $interactiveModeService,
        private readonly AutocompleteService    $autocompleteService,
    )
    {
    }

    /**
     * Enable interactive mode to disable console logging.
     *
     * This should be called BEFORE the logger is used to ensure
     * ConsoleLogChannel returns empty handlers.
     */
    public function enableInteractiveMode(): void
    {
        $this->interactiveModeService->enable();
    }

    /**
     * Start the interactive session.
     *
     * This runs both the Discord gateway event loop and the interactive terminal input
     * concurrently using ReactPHP's non-blocking I/O.
     *
     * @param LoopInterface $loop ReactPHP event loop
     * @param Discord $discord Discord instance
     */
    public function start(LoopInterface $loop, Discord $discord): void
    {
        // Create terminal and enable interactive mode
        $this->terminal = new Terminal($this->console);
        $this->terminal->switchToInteractiveMode();

        // Show welcome message
        $this->showWelcome();

        // Store input line position
        $this->inputLinePosition = $this->terminal->cursor->getPosition();

        // Make STDIN non-blocking
        stream_set_blocking(STDIN, false);
        $stdin = new ReadableResourceStream(STDIN, $loop);

        $buffer = '';
        $currentLine = '';
        $lastTabTime = 0;

        // Show the initial prompt
        $this->showPrompt();

        // Handle STDIN input
        $stdin->on('data', function ($data) use (&$buffer, &$currentLine, &$lastTabTime) {
            $buffer .= $data;

            // Process input using Key enum for better readability
            $i = 0;
            while ($i < strlen($buffer)) {
                $key = $this->extractKey($buffer, $i);

                if ($key === null) {
                    break; // Not enough data for complete key
                }

                // Update buffer position
                $keyLength = strlen($key);
                $buffer = substr($buffer, $keyLength);
                $i = 0;

                // Handle arrow keys
                if ($key === Key::UP->value || $key === Key::DOWN->value) {
                    if ($this->inCompletionMode) {
                        if ($this->isCyclingMode) {
                            // Exit cycling mode on arrow keys
                            $this->exitCompletionMode($currentLine);
                        } else {
                            // Navigate completion list
                            $direction = $key === Key::UP->value ? -1 : 1;
                            $this->moveCompletionSelection($direction, $currentLine);
                        }
                    }
                    continue;
                }

                // Handle Enter - execute command or confirm completion
                if ($key === Key::ENTER->value) {
                    if ($this->inCompletionMode) {
                        if ($this->isCyclingMode) {
                            // Exit cycling mode and continue editing
                            $this->inCompletionMode = false;
                            $this->isCyclingMode = false;
                            $this->currentCompletions = [];
                            $this->selectedCompletionIndex = 0;
                        } else {
                            // Confirm selection from list
                            $this->confirmCompletion($currentLine);
                        }
                        continue;
                    }

                    $this->console->writeln('');
                    $trimmed = trim($currentLine);
                    if (!empty($trimmed)) {
                        $this->processLine($trimmed);
                    }

                    $currentLine = '';
                    $this->inputLinePosition = $this->terminal->cursor->getPosition();
                    $this->showPrompt();
                    continue;
                }

                // Handle Tab - show/cycle autocomplete
                if ($key === Key::TAB->value) {
                    if ($this->inCompletionMode) {
                        if ($this->isCyclingMode) {
                            // Cycle to next value
                            $this->cycleToNextValue($currentLine);
                        } else {
                            // Navigate completion list
                            $this->moveCompletionSelection(1, $currentLine);
                        }
                    } else {
                        $this->handleAutocomplete($currentLine);
                    }
                    continue;
                }

                // Handle Escape - exit completion or double-tap to exit
                if ($key === Key::ESCAPE->value) {
                    $now = microtime(true);
                    $isDoubleTap = ($now - $this->lastEscapeTime) < 0.5;
                    $this->lastEscapeTime = $now;

                    if ($isDoubleTap) {
                        $this->console->writeln('');
                        $this->console->writeln('<style="fg-gray italic">Shutting down...</style>');
                        $this->shutdown();
                        exit(0);
                    }

                    if ($this->inCompletionMode) {
                        $this->exitCompletionMode($currentLine);
                    }
                    continue;
                }

                // Handle Backspace
                if ($key === Key::BACKSPACE->value) {
                    if ($this->inCompletionMode) {
                        $this->exitCompletionMode($currentLine);
                    }

                    if (strlen($currentLine) > 0) {
                        $currentLine = substr($currentLine, 0, -1);
                        $this->clearCurrentLine();
                        $this->showPrompt();
                        $this->console->write($currentLine);
                    }
                    continue;
                }

                // Handle Ctrl+C - exit
                if ($key === Key::CTRL_C->value) {
                    $this->console->writeln('');
                    $this->console->writeln('<style="fg-gray italic">Shutting down...</style>');
                    $this->shutdown();
                    exit(0);
                }

                // Regular character - add to line
                if ($this->inCompletionMode) {
                    $this->exitCompletionMode($currentLine);
                }

                $currentLine .= $key;
                $this->console->write($key);
            }
        });

        // Handle STDIN close
        $stdin->on('close', function () {
            $this->shutdown();
        });

        // Handle errors
        $stdin->on('error', function (\Exception $e) {
            $this->console->writeln('');
            $this->console->writeln('<style="bg-dark-red fg-white"> Error </style>');
            $this->console->writeln("<style='fg-red'>Input error: {$e->getMessage()}</style>");
        });

        // Set up signal handlers for graceful shutdown
        $this->setupSignalHandlers($loop);

        // Start Discord gateway in the event loop
        $loop->futureTick(function () use ($discord) {
            try {
                $discord->gateway->open();
            } catch (\Throwable $e) {
                $this->console->writeln('');
                $this->console->writeln('<style="bg-dark-red fg-white"> Error </style>');
                $this->console->writeln("<style='fg-red'>Failed to connect to Discord: {$e->getMessage()}</style>");
                $this->console->writeln("<style='fg-gray'>Check your token and network connection.</style>");
                $this->shutdown();
                exit(1);
            }
        });

        // Run the event loop (this is blocking until the loop stops)
        $loop->run();
    }

    /**
     * Extract a key from the buffer.
     */
    private function extractKey(string $buffer, int $offset): ?string
    {
        // Try to match known keys
        $remainingBuffer = substr($buffer, $offset);

        foreach (Key::cases() as $key) {
            if (str_starts_with($remainingBuffer, $key->value)) {
                return $key->value;
            }
        }

        // Single character
        if (strlen($remainingBuffer) > 0) {
            return $remainingBuffer[0];
        }

        return null;
    }

    /**
     * Process a line of user input.
     *
     * @param string $line
     */
    private function processLine(string $line): void
    {
        // Handle exit commands
        if (in_array(strtolower($line), ['exit', 'quit', '/exit', '/quit'])) {
            $this->console->writeln('');
            $this->console->writeln('<style="fg-gray italic">Shutting down...</style>');
            $this->shutdown();
            exit(0);
        }

        // Process as a prompt command
        $this->bucket->handle($line, $this->console);
    }

    /**
     * Handle autocomplete on tab press.
     */
    private function handleAutocomplete(string &$currentLine): void
    {
        $result = $this->autocompleteService->getCompletions($currentLine);

        // No matches - do nothing
        if ($result->isEmpty()) {
            return;
        }

        // Single match - complete it immediately
        if ($result->count() === 1) {
            $completion = $result->getSingleMatch();
            $type = $result->matches[0]['type'] ?? '';
            $prefix = $this->getCompletionPrefix($currentLine, $result);

            // Determine suffix based on type
            $suffix = ' ';
            if ($type === 'option') {
                // Options get '=' instead of space
                $suffix = '=';
            }

            // Clear and update line
            $this->clearCurrentLine();
            $currentLine = $prefix . $completion . $suffix;
            $this->showPrompt();
            $this->console->write($currentLine);
            return;
        }

        // For value completions with small predefined sets, cycle through them
        $firstType = $result->matches[0]['type'] ?? '';
        if ($firstType === 'value' && $result->count() <= 4) {
            // Enter cycling mode for values
            $this->currentCompletions = $result->matches;
            $this->selectedCompletionIndex = 0;
            $this->completionPrefix = $this->getCompletionPrefix($currentLine, $result);
            $this->inCompletionMode = true;
            $this->isCyclingMode = true;

            // Apply first value immediately
            $this->cycleToNextValue($currentLine);
            return;
        }

        // Multiple matches - enter completion mode with list
        $this->currentCompletions = $result->matches;
        $this->selectedCompletionIndex = 0;
        $this->completionPrefix = $this->getCompletionPrefix($currentLine, $result);
        $this->inCompletionMode = true;

        // Don't auto-apply first completion - just show the list
        // Keep the current input as-is
        $this->showCompletionList($currentLine);
    }

    /**
     * Get the prefix that should remain when applying completions.
     */
    private function getCompletionPrefix(string $currentLine, AutocompleteResult $result): string
    {
        // The autocomplete service now provides the prefix in the parsed result
        // But we need to recalculate it here for the interactive context
        $trimmed = trim($currentLine);
        $hasTrailingSpace = str_ends_with($currentLine, ' ');
        $parts = preg_split('/\s+/', $trimmed);

        if (count($parts) === 1 && !$hasTrailingSpace) {
            // Completing command name
            return '';
        }

        // For options, keep everything typed so far
        // This ensures appending instead of replacing
        if ($hasTrailingSpace) {
            return $trimmed . ' ';
        }

        // If last part starts with --, we're completing an option name
        $lastPart = end($parts);
        if (str_starts_with($lastPart, '--')) {
            // Keep everything except the incomplete option
            $prefix = implode(' ', array_slice($parts, 0, -1));
            return $prefix ? $prefix . ' ' : '';
        }

        // Otherwise keep everything except the last part (completing value)
        $prefix = implode(' ', array_slice($parts, 0, -1));
        return $prefix ? $prefix . ' ' : '';
    }

    /**
     * Move completion selection up or down.
     */
    private function moveCompletionSelection(int $direction, string &$currentLine): void
    {
        if (!$this->inCompletionMode || empty($this->currentCompletions)) {
            return;
        }

        // Clear previous display
        $this->clearCompletionDisplay();

        $this->selectedCompletionIndex += $direction;

        // Wrap around
        if ($this->selectedCompletionIndex < 0) {
            $this->selectedCompletionIndex = count($this->currentCompletions) - 1;
        } elseif ($this->selectedCompletionIndex >= count($this->currentCompletions)) {
            $this->selectedCompletionIndex = 0;
        }

        // Redraw completion list (without applying to input)
        $this->showCompletionList($currentLine);
    }

    /**
     * Confirm the current completion selection.
     */
    private function confirmCompletion(string &$currentLine): void
    {
        if (!$this->inCompletionMode) {
            return;
        }

        // Get selected completion
        $selectedMatch = $this->currentCompletions[$this->selectedCompletionIndex];
        $completion = $selectedMatch['value'];
        $type = $selectedMatch['type'] ?? '';

        // Clear completion display
        $this->clearCompletionDisplay();

        // Determine suffix based on type
        $suffix = ' ';
        if ($type === 'option') {
            // Options get '=' instead of space
            $suffix = '=';
        }

        // Apply completion to input line
        $newLine = $this->completionPrefix . $completion . $suffix;
        $this->clearCurrentLine();
        $currentLine = $newLine;
        $this->showPrompt();
        $this->console->write($currentLine);

        $this->inCompletionMode = false;
        $this->isCyclingMode = false;
        $this->currentCompletions = [];
        $this->selectedCompletionIndex = 0;
    }

    /**
     * Exit completion mode without confirming.
     */
    private function exitCompletionMode(string &$currentLine): void
    {
        if (!$this->inCompletionMode) {
            return;
        }

        $this->clearCompletionDisplay();
        $this->inCompletionMode = false;
        $this->isCyclingMode = false;
        $this->currentCompletions = [];
        $this->selectedCompletionIndex = 0;

        // Redraw the current line so input is visible
        $this->showPrompt();
        $this->console->write($currentLine);
    }

    /**
     * Cycle to the next value in cycling mode.
     */
    private function cycleToNextValue(string &$currentLine): void
    {
        if (!$this->isCyclingMode || empty($this->currentCompletions)) {
            return;
        }

        // Get the current value
        $selectedMatch = $this->currentCompletions[$this->selectedCompletionIndex];
        $completion = $selectedMatch['value'];

        // Apply completion to input line (values get space suffix)
        $newLine = $this->completionPrefix . $completion . ' ';
        $this->clearCurrentLine();
        $currentLine = $newLine;
        $this->showPrompt();
        $this->console->write($currentLine);

        // Move to next index (wrap around)
        $this->selectedCompletionIndex++;
        if ($this->selectedCompletionIndex >= count($this->currentCompletions)) {
            $this->selectedCompletionIndex = 0;
        }
    }

    /**
     * Show completion list with current selection highlighted.
     */
    private function showCompletionList(string &$currentLine): void
    {
        if (!$this->inputLinePosition) {
            return;
        }

        // Save cursor position and use clearAfter to clear below
        $this->terminal->cursor->place($this->inputLinePosition);
        $this->terminal->cursor->clearAfter();

        // Write newlines to create space
        $this->console->writeln('');
        $this->console->writeln('');

        foreach ($this->currentCompletions as $index => $match) {
            $value = $match['value'];
            $description = $match['description'] ?? '';
            $type = $match['type'] ?? '';
            $isSelected = ($index === $this->selectedCompletionIndex);

            // Build the line with proper indicators
            if ($isSelected) {
                $indicator = "<style='fg-magenta'>→</style>";
            } else {
                $indicator = " ";
            }

            $coloredValue = $this->getColoredValue($value, $type);
            $descPart = $description ? " <style='fg-gray dim'>· {$description}</style>" : '';

            $this->console->writeln("  {$indicator} {$coloredValue}{$descPart}");
        }

        // Add blank line for padding
        $this->console->writeln('');

        // Return cursor to input line
        $this->terminal->cursor->place($this->inputLinePosition);
    }

    /**
     * Get colored value based on type.
     */
    private function getColoredValue(string $value, string $type): string
    {
        return match($type) {
            'prompt' => "<style='fg-cyan'>{$value}</style>",
            'option' => "<style='fg-yellow'>{$value}</style>",
            'value' => "<style='fg-green'>{$value}</style>",
            default => $value,
        };
    }

    /**
     * Clear the completion display.
     */
    private function clearCompletionDisplay(): void
    {
        if (!$this->inputLinePosition) {
            return;
        }

        // Go to input line and clear everything below it
        $this->terminal->cursor->place($this->inputLinePosition);
        $this->terminal->cursor->clearAfter();

        $this->completionDisplayLines = 0;
    }

    /**
     * Clear the current input line.
     */
    private function clearCurrentLine(): void
    {
        if ($this->inputLinePosition) {
            $this->terminal->cursor->place($this->inputLinePosition);
        }
        $this->terminal->cursor->clearLine();
    }

    /**
     * Show welcome message.
     */
    private function showWelcome(): void
    {
        $this->console->writeln('');
        $this->console->writeln('<style="fg-cyan bold">Tempcord Interactive Mode</style>');
        $this->console->writeln("<style='fg-gray'>Type <style='fg-cyan'>/help</style> to see available commands, or <style='fg-cyan'>exit</style> to quit.</style>");
        $this->console->writeln('');
        $this->console->writeln("<style='fg-gray'>Controls:</style>");
        $this->console->writeln("<style='fg-gray'>  • Autocomplete: <style='fg-yellow'>Tab</style> to show, <style='fg-yellow'>↑↓</style> to navigate, <style='fg-yellow'>Enter</style> to confirm</style>");
        $this->console->writeln("<style='fg-gray'>  • Exit: <style='fg-yellow'>Ctrl+C</style> or press <style='fg-yellow'>Esc</style> twice</style>");
        $this->console->writeln('');
    }

    /**
     * Show the prompt indicator.
     */
    private function showPrompt(): void
    {
        $this->console->write("<style='fg-green bold'>» </style>");
    }

    /**
     * Shutdown gracefully.
     */
    private function shutdown(): void
    {
        if ($this->terminal) {
            $this->terminal->switchToNormalMode();
        }
        $this->tempcord->shutdown();
    }

    /**
     * Setup signal handlers for graceful shutdown.
     *
     * @param LoopInterface $loop
     */
    private function setupSignalHandlers(LoopInterface $loop): void
    {
        if (!function_exists('pcntl_signal')) {
            return;
        }

        // SIGINT (Ctrl+C)
        $loop->addSignal(SIGINT, function () {
            $this->console->writeln('');
            $this->console->writeln('<style="fg-gray italic">Shutting down...</style>');
            $this->shutdown();
            exit(0);
        });

        // SIGTERM
        $loop->addSignal(SIGTERM, function () {
            $this->console->writeln('');
            $this->console->writeln('<style="fg-gray italic">Shutting down...</style>');
            $this->shutdown();
            exit(0);
        });
    }
}
