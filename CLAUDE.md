# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Tempcord is a PHP framework for building Discord bots. It provides an attribute-based approach for defining Discord commands and events, built on top of Tempest Console and Ragnarok Fenrir Discord library.

## Development Commands

```bash
# Install dependencies
composer install

# Run tests
composer test
```

## Architecture

### Core Components

- **Tempcord** (`src/Tempcord.php`) - Main entry point that boots the Discord connection via Ragnarok Fenrir and registers the commands registry as an extension
- **TempcordConfig** (`src/TempcordConfig.php`) - Configuration holder for Discord token and gateway intents

### Command System

Commands use PHP attributes for definition and are auto-discovered by Tempest's discovery system:

1. **Command Attribute** (`src/Attributes/Commands/Command.php`) - Class-level attribute for defining slash commands. If no name is provided, it derives from class name (removes "Command" prefix/suffix, converts to snake_case)

2. **Subcommand Attribute** (`src/Attributes/Commands/Subcommand.php`) - Method-level attribute for subcommands within a command class

3. **Option Attribute** (`src/Attributes/Commands/Option.php`) - Parameter-level attribute for command options. Type is inferred from PHP parameter type (string, int, float, bool, User, Channel, Role)

4. **CommandsDiscovery** (`src/Discoveries/CommandsDiscovery.php`) - Tempest discovery that finds all `#[Command]` attributed classes

5. **CommandsRegistry** (`src/Registries/CommandsRegistry.php`) - Fenrir extension that registers commands with Discord and handles incoming interactions

6. **CommandsBucket** (`src/Support/Commands/CommandsBucket.php`) - Stores discovered commands and routes interactions to handlers

7. **CommandHandler** (`src/Support/Commands/CommandHandler.php`) - Executes command/subcommand handlers with mapped arguments

### Interaction Flow

1. `CommandsDiscovery` finds `#[Command]` classes during Tempest boot
2. Commands are added to `CommandsBucket` via `CommandsRegistry`
3. On bot boot, `CommandsRegistry` registers commands with Discord API
4. Incoming `InteractionCreate` events are routed through `CommandsBucket.handle()`
5. `CommandHandler` resolves the handler method and maps option values to parameters

### Response System

- **CommandInteraction** (`src/CommandInteraction.php`) - Extended interaction class with fluent response builder
- **InteractionResponse** (`src/Support/Responses/InteractionResponse.php`) - Fluent response builder with decorators (success, warning, error, content)
- **ResponseDecorator** pattern in `src/Support/Responses/Decorators/` for composable response styling

### Prompts System

Interactive terminal prompts for runtime bot management and control:

1. **Prompt Attribute** (`src/Attributes/Prompts/Prompt.php`) - Class-level attribute for defining interactive prompts
2. **PromptOption Attribute** (`src/Attributes/Prompts/PromptOption.php`) - Parameter-level attribute for prompt options with autocomplete support
3. **PromptsDiscovery** (`src/Discoveries/PromptsDiscovery.php`) - Discovers all `#[Prompt]` attributed classes
4. **PromptsRegistry** (`src/Registries/PromptsRegistry.php`) - Registers discovered prompts into the bucket
5. **PromptsBucket** (`src/Support/Prompts/PromptsBucket.php`) - Routes prompt commands to handlers with error handling
6. **InteractiveSession** (`src/Support/Prompts/InteractiveSession.php`) - Terminal management for interactive mode using ReactPHP
7. **AutocompleteService** (`src/Support/Prompts/AutocompleteService.php`) - Context-aware autocomplete for commands, options, and values

### Console Commands

- `boot` - Starts the Discord bot in blocking mode (`php tempcord boot`)
- `interactive` / `i` - Starts the bot in interactive mode with live command prompt (`php tempcord interactive`)
  - Features tab-based autocomplete, arrow key navigation, and fuzzy command matching
  - Controls: Tab (autocomplete), ↑↓ (navigate), Enter (confirm), Esc×2 or Ctrl+C (exit)
- `register` - Register commands without starting bot
- `commands:list` - List discovered commands
