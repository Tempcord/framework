# Tempcord Framework — Product Requirements Document (PRD)

## Overview
- Tempcord is a PHP framework for building Discord bots using modern PHP attributes, DI, and a clean event-driven architecture.
- It wraps `ragnarok/fenrir` (Discord gateway and REST) and `tempest/console`/`tempest/core` to deliver a developer-friendly experience for command discovery, registration, and interaction handling.
- This PRD defines the product goals, scope, requirements, and success criteria for the framework.

## Objectives
- Enable rapid development of Discord bots in PHP with attribute-based commands and strong typing.
- Provide automatic discovery and registration of commands for global and guild scopes.
- Offer a predictable lifecycle: discover → register → route → handle interactions.
- Ensure reliability, security, and performance suitable for production bots.
- Deliver a pleasant DX: simple CLI, clear configuration, helpful logging, and testability.

## Target Users
- PHP developers building Discord bots for communities or applications.
- Teams needing maintainable, testable bot frameworks with extensibility and clear architecture.

## Value Proposition
- Attribute-first API reduces boilerplate and cognitive load.
- Auto-discovery and router streamline mapping from Discord commands to PHP handlers.
- Adapters around Fenrir standardize registration and events, minimizing protocol surface.
- CLI tooling and DI-based initialization simplify setup and operation.

## In Scope
- Attribute-based command definition (`#[Command]`, `#[Option]`, `#[Subcommand]`, `#[SubcommandGroup]`).
- Command discovery and aggregation across configurable locations.
- Registration via Fenrir REST for global and guild commands.
- Event bus abstraction and filtering for `INTERACTION_CREATE` (application commands).
- Router mapping of `ApplicationCommand` IDs to handlers with typed option resolution.
- CLI commands to boot the gateway and register commands.
- Configuration via `TempcordConfig` (token, intents, optional dev guild).
- Logging facilities suitable for local dev and production observability.
- Example playground showcasing typical usage and best practices.

## Out of Scope (initial)
- Non-application interactions (message content monitoring, voice, reactions) beyond routing extension points.
- Full-blown plugin marketplace or GUI management.
- Hosted infrastructure or managed bot runtime.

## User Stories
- As a developer, I define a command with attributes and it is auto-discovered without manual wiring.
- As an operator, I run `php tempcord boot --register` to register commands and start the bot.
- As a developer, options in a command map to typed PHP parameters, including `User`, `Channel`, `Role` resolution via REST.
- As a maintainer, I can organize commands via subcommands and groups.
- As a tester, I can validate registration and routing without a live gateway.

## Functional Requirements
- Command Definition
  - Must support `#[Command]` with name, description, scopes (global/guild) and options.
  - Must support `#[Option]` with types, required/optional, and autocomplete.
  - Must support `#[Subcommand]` and `#[SubcommandGroup]`.
  - Should support `#[HandledBy]` to direct to a specific handler method/class.
- Discovery & Aggregation
  - Must discover commands from configured namespaces/paths.
  - Must merge commands with identical names via `CommandsBucket` logic.
- Registration
  - Must register commands to Discord via Fenrir REST for global and guild scopes.
  - Must provide immediate registration mode for testing environments.
- Event Handling
  - Must subscribe to `READY` and `INTERACTION_CREATE` and only route application commands.
  - Must adapt events via `EventBus` abstraction for portability.
- Routing & Invocation
  - Must map `ApplicationCommand` IDs to command metadata.
  - Must resolve and validate options, fetching Discord entities as needed.
  - Must support subcommand dispatch and grouped subcommands.
- CLI
  - Must provide `boot` and `register` commands with flags for registration and target guild.
  - Should provide helpful output and exit codes for CI usage.
- Configuration
  - Must provide `TempcordConfig` with token, intents, optional `devGuildId`.
  - Must integrate with a DI container for initialization (`TempcordInitializer`).
- Logging & Observability
  - Should provide structured logs and levels; must avoid leaking secrets.
  - Should allow plugging custom log handlers.
- Testing Support
  - Should include unit tests for discovery, registration, routing, and handler resolution.

## Non-Functional Requirements
- Reliability: predictable startup and graceful error handling; retries on transient REST failures.
- Performance: efficient discovery and routing; low-latency interaction handling (<100ms internal overhead where feasible).
- Security: never log tokens; validate inputs; principle of least privilege for intents.
- Compatibility: PHP 8.3+; Fenrir versions pinned via Composer; stable public interfaces.
- Extensibility: adapters/interfaces for registrar, event bus, promise handling.
- Observability: logs with correlation IDs; optional metrics hooks.

## Architecture Overview
- Composition
  - `Tempcord` façade: `registerCommands()`, `boot()` lifecycle.
  - `CommandsDiscovery` → `CommandsBucket` → `CommandsRegistry` → `Router` → `CommandHandler`.
  - Adapters: `FenrirCommandRegistrar`, `FenrirEventBus`, `FenrirPromiseAdapter`.
  - DI and console integration via Tempest.
- Flow
  - Discover annotated classes; aggregate.
  - On boot: register (global/guild), subscribe to events.
  - On interaction: route by command ID, resolve options, invoke handler.
- Configuration
  - `TempcordConfig` defines token, intents, dev guild; provided via app config (e.g., `playground/discord.config.php`).

## Interfaces & CLI
- CLI Commands
  - `php tempcord boot` — start gateway; `--register` to register before boot; `--guild <id>` for scoped registration.
  - `php tempcord register` — register without booting (optional convenience).
- Public Interfaces
  - `CommandRegistrar` — register global/guild commands.
  - `EventBus` — subscribe/emit Discord events.
  - `Thenable` — promise adapter for async flows.

## Error Handling & Edge Cases
- Duplicate command names: merge or reject per policy with clear logs.
- Missing permissions/intents: fail fast with actionable error.
- REST failures: retry/backoff; partial registration reporting.
- Handler resolution ambiguities: deterministic method selection (`#[HandledBy]`, single public method, or `__invoke`).

## Metrics & Success Criteria
- Developer Productivity: time to first command (<10 minutes) and lines of code.
- Stability: error rate in registration/dispatch; mean recovery time.
- Performance: median interaction handling overhead.
- Adoption: number of projects using Tempcord; community contributions.

## Milestones
- v0.1 (MVP)
  - Attribute commands, discovery, router, registrar, CLI boot/register, basic logging.
  - Unit tests for discovery and routing.
- v0.2
  - Autocomplete, richer option typing, improved logging/observability, testing utilities.
- v1.0 (Stable)
  - Hardened error handling, documented extension points, performance tuning, comprehensive docs.

## Acceptance Criteria
- Commands can be defined with attributes and discovered without manual wiring.
- `php tempcord boot --register` registers commands and starts gateway successfully.
- Interactions are routed to the correct handler with correct typed options.
- Global and guild registration supported; failures produce clear actionable logs.
- Secrets are never logged; configuration is DI-friendly.

## Risks & Mitigation
- Discord API changes: pin dependencies; maintain adapters; add compatibility tests.
- Misconfiguration (tokens/intents): provide validations and clear error messages.
- Performance regressions: profiling in CI; keep O(n) paths tight in discovery.
- Ecosystem drift: document public interfaces; semantic versioning.

## References
- External docs: https://tempcord.dev
- Dependencies: `ragnarok/fenrir`, `tempest/console`, `tempest/core`
- Example config: `playground/discord.config.php`
