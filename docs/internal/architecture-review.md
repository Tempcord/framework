# Архитектурный обзор

Дата: 2026-10-31, версия 1.3.0. Пути в тексте — на момент обзора (1.x); после
реструктуризации каталогов смотри таблицу соответствия в конце файла.

Внутренний документ: не входит в `docs/index.json` и не генерируется
`composer docs`.

## Общая оценка

Ядро спроектировано правильно: **атрибуты → Compiler → неизменяемые
Definitions → Registries → Binder/Dispatcher**. Рефлексия выполняется один раз
при discovery, runtime работает только с готовыми definition'ами.

Слабые места — по краям: модули, не относящиеся к ядру (Pagination, Logging,
часть Cache), обходы багов в собственном форке `tempcord/discord-php`, плоская
структура каталогов и рассинхрон README с кодом.

## Что сделано хорошо

- Разделение compile-time и runtime; handler'ы раскладываются в плоский список
  по пути (`music.playlist.play`), dispatch — поиск по ключу.
- Проверки при старте (`DeclaredMiddleware::checked`, `CommandCompiler::check`)
  роняют бота при загрузке, а не при первом вызове.
- `CustomId` с `{placeholders}` в обе стороны; `ComponentsRegistry` с быстрым
  поиском точных id и приоритетом точного id над шаблоном.
- `Outcome` + `OutcomeReporter`: результат вместо печати, легко тестировать.
- `MiddlewarePipeline`: ленивые middleware, аргументы резолвятся внутри цепочки.
- Изоляция ошибок: каждый dispatch в `async()` + `try/catch`.
- `CommandRegistrar`: bulk overwrite по scope.
- `RequiresPermissions`, `DirectMessage`, `TargetResolver`, enum-опции +
  `Choosable`.
- Опциональная локализация через `NullLocalizations` и `interface_exists`.

## Баги и архитектурные проблемы

Отсортировано по важности.

- [ ] **1. Утечка памяти в кеше сообщений.** `Cache::putMessage` вызывается на
  каждый `MESSAGE_CREATE`, удаление — только на `MESSAGE_DELETE`. Комментарий
  обещает «short-lived», но вытеснения нет. Нужен лимит (LRU / ring buffer на
  guild или канал) или TTL.
- [x] **2. Дублирование handler'ов после reconnect.** Исправлено в 2.0.1
  (`once(READY)`). В боте apex-ua воспроизведено: Discord отвечал 40060.
  `Tempcord::__construct` регистрирует extension'ы на каждый `READY`. При
  невозобновляемом reconnect `Connection::startNewConnection()` заново делает
  identify → новый `READY` → `initialize()` создаёт ещё один
  `FilteredEventEmitter` на том же `gateway->events`. Каждая команда и кнопка
  срабатывает N раз.
- [ ] **3. USER/CHANNEL/ROLE-опции всегда идут в REST.**
  `OptionValueResolver` делает `await rest->user->get()` и т. п., хотя Discord
  присылает объекты в `interaction.data.resolved` (их уже использует
  `TargetResolver`). Лишняя задержка, расход rate limit, риск 3-секундного
  таймаута ответа.
- [ ] **4. `directMessage: false`, скорее всего, не работает.** Форк в
  `CommandBuilder::setDmPermission` пишет `dm_permissions`, а поле Discord —
  `dm_permission` (и оно устарело в пользу `contexts`).
- [ ] **5. `CommandDefinition::mergedWith` теряет данные.** Не переносит
  `handler` (entry point); одноимённая `#[SubcommandGroup]` из второго класса
  затирает группу первого; расхождения `permissions`/`isNsfw` молча
  игнорируются.
- [ ] **6. `#[Command]` разрешён на методе, но игнорируется.** `TARGET_METHOD`
  в атрибуте, а `CommandsDiscovery` читает только атрибуты класса.
- [x] **7. Нарушение слоёв.** `CustomId` лежит в `Runtime/`, но импортируется
  в `Compiler/` и `Definitions/`. `Runtime/` — 22 разнородных файла.
- [ ] **8. Несимметричные registries.** `EventsRegistry::listen()` сам
  привязывает listener'ы и тянет `Container`, в отличие от
  `CommandBinder`/`ComponentBinder`. Нужен `EventBinder`.
- [ ] **9. Ошибка handler'а не видна пользователю.** Dispatcher'ы только
  логируют; пользователь видит «The application did not respond». Нужен хук или
  дефолтный ephemeral-ответ.
- [ ] **10. Cache пишет в DTO библиотеки.** `CacheSubscriber` выставляет
  `oldMember`/`newMember`/`oldMessage`/`oldState` на объектах событий форка.
  Неявный контракт: зависит от порядка подписки, при `cache: false` поля молча
  `null`.

## Мусор

- [ ] Строка на украинском в `Pagination::page()` — без локализации и
  переопределения.
- [ ] Мёртвый код: `$type` и `getLogLevel()` в `ConsoleLogHandler`.
- [ ] Неиспользуемый параметр `$action` в `Pagination::page()`.
- [ ] `Tempcord::$booted` — публичное изменяемое поле, нужно только тесту.
- [ ] `count()`, `classes()`, `isGlobal()` — используются только в тестах.
- [ ] `PluginsRegistry` с `?Container` и фолбэком `new $plugin()` — удобство
  тестов в прод-коде.
- [ ] `tempest/event-bus` в `require` — не используется.
- [ ] `tempest/database` в `require` — только ради `DatabasePaginator`, место в
  `suggest`.
- [ ] Нет `declare(strict_types=1)` ни в одном файле `src/`.
- [x] README устарел: namespace `CyberWolf\Discord\…`, несуществующий
  `respondWithMessage`, несуществующая `php tempcord register`,
  `#[Event(name: 'message_create')]` вместо `MESSAGE_CREATE`, badge 0.5.0,
  фейковый badge «build passing».
- [ ] `"php tempcord init"` в ошибке `DiscordInitializer` — команды нет в этом
  пакете.
- [ ] Фильтр логов по подстрокам чужих сообщений (с опечаткой `esablished`) в
  `ConsoleLogChannel` — понизить уровень этих логов в форке.
- [x] Общий namespace `Tempcord\Discord\` у фреймворка (`src/Discord/`) и
  форка — два PSR-4 корня на один префикс.
- [ ] `Interfaces\Autocomplete` и `Attributes\Autocomplete` — одно имя;
  namespace `AutoCompletes` с большой C.
- [ ] `ArrayAutocomplete(isList: bool)` — флаг дублирует `array_is_list` в
  `ChoiceFactory`.

### Обходы, которые надо перенести в форк

- [ ] `CommandBuilderFactory::payloadFor` — обход бага permissions; в форке он
  уже исправлен, обход можно удалить.
- [ ] `CommandRegistrar::buildHttp()` — второй HTTP-клиент; в форке уже есть
  `GlobalCommand::bulkOverwriteApplicationCommands`.
- [ ] `Runtime/Autocomplete/InteractionCallbackBuilder.php` — подкласс ради
  `choices`. В 2.0.1 исправлен пустой список: раньше `data.choices` не
  отправлялось, и Discord отвечал 50035. Сам подкласс ещё надо перенести в форк.
- [ ] `src/Discord/AllCommandExtension.php` — расширение ради autocomplete.

## Оверинжиниринг

- Генератор документации (`tools/`, ~700 строк) для ~25 публичных символов.
  Идея проверки свежести хорошая, реализация тяжёлая.
- Объём комментариев: много прозы, пересказывающей историю изменений, — это
  материал для git log.
- `Pagination` в ядре: stateful-синглтон со своим `#[Button]` и зависимостью от
  `tempest/database`. Кандидат в отдельный плагин.
- Свой `ConsoleLogChannel`/`ConsoleLogHandler` с обходом бага парсера Tempest —
  лучше отправить фикс в Tempest.
- `semantic-release` + `prune-releases.py` + lint заголовков PR для одного
  мейнтейнера.

## Несогласованности

- Три способа резолвить параметры с разными правилами: `ArgumentResolver`
  (имя → тип), `ComponentArgumentResolver` (тип → имя),
  `AutocompleteResolver::argumentsFor`.
- Обёртки `ButtonInteraction`/`ModalSubmitInteraction` создаются до трёх раз на
  событие.
- `$next($x)` в middleware компонента не передаёт подменённый interaction в
  handler.
- `MENTIONABLE` бросает исключение в runtime, а компилятор не отклоняет его при
  старте.
- `#[Event]` — строка без проверки существования события.

## Порядок работ

1. ~~Читабельность: реструктуризация каталогов (Declare / Compile / Runtime /
   Support).~~ Сделано, см. ниже.
2. Ограничить кеш сообщений.
3. Регистрировать extension'ы один раз.
4. USER/CHANNEL/ROLE из `data.resolved`.
5. Починить `dm_permission`/`contexts` в форке и перенести туда обходы.
6. Убрать `TARGET_METHOD` с `#[Command]`, починить `mergedWith`.
7. Вынести `Pagination` в плагин, удалить лишние зависимости.
8. ~~Переписать README.~~ Сделано.
9. `EventBinder`, общий resolver параметров, сокращение комментариев.

## Структура каталогов (2.0)

Шаг 1 выполнен. `src/` разбит на четыре зоны, инициализаторы и корневые
классы остались в корне:

```
src/
  Tempcord.php, TempcordConfig.php
  TempcordInitializer.php, DiscordInitializer.php, LocalizationInitializer.php
  Declare/   что пишет автор бота: атрибуты и контракты
    Command/ Component/ Event/ Contracts/
  Compile/   атрибуты → definitions, discovery, registries
    Command/ (+ Definitions/) Component/ Event/ Plugin/ Middleware/
  Runtime/   привязка к gateway, dispatch, регистрация в Discord, boot
    Command/ Autocomplete/ Component/ Event/ Middleware/ Plugin/ Registration/ Boot/
  Support/   необязательные возможности поверх ядра
    Cache/ Localization/ Logging/ Messaging/ Middleware/ Pagination/ Autocomplete/
```

`tests/Unit/` повторяет ту же структуру.

Закрыто переездом:
- `CustomId` больше не лежит в `Runtime/` — теперь `Compile\Component\CustomId`
  (п. 7, часть про слои).
- Фреймворк больше не использует namespace `Tempcord\Discord\` форка: классы из
  `src/Discord/` переехали в `Runtime\…`.
- Namespace `AutoCompletes` с большой C исчез (`Support\Autocomplete`).

Осталось: `Declare\Command\Autocomplete` (атрибут) и
`Declare\Contracts\Autocomplete` (интерфейс) всё ещё называются одинаково.

### Общий код в трейтах (`Concerns/`)

| Трейт | Что делает | Где используется |
| --- | --- | --- |
| `Runtime\Concerns\ContainsFailures` | `contained()`: запускает код в fiber, ловит исключение и пишет его в лог | `CommandDispatcher`, `ComponentDispatcher`, `EventDispatcher`, `AutocompleteResponder` |
| `Compile\Concerns\ReadsParameterTypes` | `parameterType()`: тип параметра с проверкой `hasType()`; `isBackedEnum()` | `CommandCompiler`, `ArgumentResolver`, `ComponentArgumentResolver`, `OptionValueResolver`, `TargetResolver`, `AutocompleteResolver` |
| `Compile\Concerns\DerivesNames` | `declaredName()` (строка или enum), `snakeName()`, `nameFromClass()` (убирает аффиксы вроде `Command`) | `CommandCompiler`, `ComponentCompiler` |
| `Compile\Concerns\RequiresInvoker` | `requireInvoker()`: ищет `__invoke`, если его нет — ошибка при discovery | `CommandCompiler`, `ComponentCompiler`, `EventCompiler` |

Трейты с проверкой типов параметров лежат в `Compile/`: `Runtime` может зависеть
от `Compile`, но не наоборот. Тексты ошибок и логов остались прежними.

Сознательно не вынесено:
- `apply()` в Discovery-классах. Это четыре строки, и в каждом классе свой реестр.
- Сборка обёрток взаимодействия в `ComponentDispatcher::wrap()` и
  `ComponentArgumentResolver::wrapperFor()`. Код похож, но выбор идёт по разным
  ключам (`ComponentKind` и тип параметра). Если объединять, то методом на
  `ComponentKind`, а не трейтом.
- Значение по умолчанию или «Missing required parameter» в двух резолверах.
  Управление потоком там разное (`continue` в цикле и `return`).

### Соответствие namespace (префикс `Tempcord\` опущен)

| Было (1.x) | Стало (2.0) |
|---|---|
| `Compiler\CommandCompiler` | `Compile\Command\CommandCompiler` |
| `Discoveries\CommandsDiscovery` | `Compile\Command\CommandsDiscovery` |
| `Registries\CommandsRegistry` | `Compile\Command\CommandsRegistry` |
| `Definitions\AutocompleteDefinition` | `Compile\Command\Definitions\AutocompleteDefinition` |
| `Definitions\CommandDefinition` | `Compile\Command\Definitions\CommandDefinition` |
| `Definitions\HandlerDefinition` | `Compile\Command\Definitions\HandlerDefinition` |
| `Definitions\OptionDefinition` | `Compile\Command\Definitions\OptionDefinition` |
| `Definitions\SubcommandDefinition` | `Compile\Command\Definitions\SubcommandDefinition` |
| `Definitions\SubcommandGroupDefinition` | `Compile\Command\Definitions\SubcommandGroupDefinition` |
| `Compiler\ComponentCompiler` | `Compile\Component\ComponentCompiler` |
| `Definitions\ComponentDefinition` | `Compile\Component\ComponentDefinition` |
| `Enums\ComponentKind` | `Compile\Component\ComponentKind` |
| `Runtime\ComponentMatch` | `Compile\Component\ComponentMatch` |
| `Discoveries\ComponentsDiscovery` | `Compile\Component\ComponentsDiscovery` |
| `Registries\ComponentsRegistry` | `Compile\Component\ComponentsRegistry` |
| `Runtime\CustomId` | `Compile\Component\CustomId` |
| `Compiler\EventCompiler` | `Compile\Event\EventCompiler` |
| `Definitions\EventDefinition` | `Compile\Event\EventDefinition` |
| `Discoveries\EventsDiscovery` | `Compile\Event\EventsDiscovery` |
| `Registries\EventsRegistry` | `Compile\Event\EventsRegistry` |
| `Compiler\DeclaredMiddleware` | `Compile\Middleware\DeclaredMiddleware` |
| `Discoveries\PluginsDiscovery` | `Compile\Plugin\PluginsDiscovery` |
| `Registries\PluginsRegistry` | `Compile\Plugin\PluginsRegistry` |
| `Attributes\Autocomplete` | `Declare\Command\Autocomplete` |
| `Attributes\Command` | `Declare\Command\Command` |
| `Attributes\Option` | `Declare\Command\Option` |
| `Attributes\SubcommandGroup` | `Declare\Command\SubcommandGroup` |
| `Attributes\Subcommand` | `Declare\Command\Subcommand` |
| `Attributes\Button` | `Declare\Component\Button` |
| `Attributes\ModalSubmit` | `Declare\Component\ModalSubmit` |
| `Attributes\SelectMenu` | `Declare\Component\SelectMenu` |
| `Interfaces\Autocomplete` | `Declare\Contracts\Autocomplete` |
| `Interfaces\Choosable` | `Declare\Contracts\Choosable` |
| `Interfaces\Middleware` | `Declare\Contracts\Middleware` |
| `Plugins\Plugin` | `Declare\Contracts\Plugin` |
| `Attributes\Event` | `Declare\Event\Event` |
| `Localization\LocalizationInitializer` | `LocalizationInitializer` |
| `Runtime\AutocompleteResolver` | `Runtime\Autocomplete\AutocompleteResolver` |
| `Runtime\AutocompleteResponder` | `Runtime\Autocomplete\AutocompleteResponder` |
| `Runtime\ChoiceFactory` | `Runtime\Autocomplete\ChoiceFactory` |
| `Discord\InteractionCallbackBuilder` | `Runtime\Autocomplete\InteractionCallbackBuilder` |
| `ConsoleCommands\BootCommand` | `Runtime\Boot\BootCommand` |
| `Runtime\OutcomeLevel` | `Runtime\Boot\OutcomeLevel` |
| `Runtime\OutcomeReporter` | `Runtime\Boot\OutcomeReporter` |
| `Runtime\Outcome` | `Runtime\Boot\Outcome` |
| `Discord\AllCommandExtension` | `Runtime\Command\AllCommandExtension` |
| `Runtime\ArgumentResolver` | `Runtime\Command\ArgumentResolver` |
| `Runtime\CommandBinder` | `Runtime\Command\CommandBinder` |
| `Runtime\CommandDispatcher` | `Runtime\Command\CommandDispatcher` |
| `Runtime\OptionValueResolver` | `Runtime\Command\OptionValueResolver` |
| `Runtime\TargetResolver` | `Runtime\Command\TargetResolver` |
| `Runtime\ComponentArgumentResolver` | `Runtime\Component\ComponentArgumentResolver` |
| `Runtime\ComponentBinder` | `Runtime\Component\ComponentBinder` |
| `Runtime\ComponentDispatcher` | `Runtime\Component\ComponentDispatcher` |
| `Discord\ComponentExtension` | `Runtime\Component\ComponentExtension` |
| `Runtime\EventDispatcher` | `Runtime\Event\EventDispatcher` |
| `Runtime\MiddlewarePipeline` | `Runtime\Middleware\MiddlewarePipeline` |
| `Runtime\PluginBooter` | `Runtime\Plugin\PluginBooter` |
| `Discord\CommandBuilderFactory` | `Runtime\Registration\CommandBuilderFactory` |
| `Runtime\CommandRegistrar` | `Runtime\Registration\CommandRegistrar` |
| `AutoCompletes\ArrayAutocomplete` | `Support\Autocomplete\ArrayAutocomplete` |
| `Cache\CacheSubscriber` | `Support\Cache\CacheSubscriber` |
| `Cache\Cache` | `Support\Cache\Cache` |
| `Cache\GuildState` | `Support\Cache\GuildState` |
| `Localization\CatalogLocalizations` | `Support\Localization\CatalogLocalizations` |
| `Enums\DiscordLocale` | `Support\Localization\DiscordLocale` |
| `Localization\LocalizationProvider` | `Support\Localization\LocalizationProvider` |
| `Localization\NullLocalizations` | `Support\Localization\NullLocalizations` |
| `Logging\ConsoleLogChannel` | `Support\Logging\ConsoleLogChannel` |
| `Logging\Handlers\ConsoleLogHandler` | `Support\Logging\ConsoleLogHandler` |
| `Messaging\DirectMessage` | `Support\Messaging\DirectMessage` |
| `Middleware\RequiresPermissions` | `Support\Middleware\RequiresPermissions` |
| `Pagination\ArrayPaginator` | `Support\Pagination\ArrayPaginator` |
| `Pagination\DatabasePaginator` | `Support\Pagination\DatabasePaginator` |
| `Pagination\Page` | `Support\Pagination\Page` |
| `Pagination\PaginationSession` | `Support\Pagination\PaginationSession` |
| `Pagination\Pagination` | `Support\Pagination\Pagination` |
| `Pagination\Paginator` | `Support\Pagination\Paginator` |
