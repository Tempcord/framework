## [0.14.0](https://github.com/Tempcord/framework/compare/v0.13.0...v0.14.0) (2026-09-03)

### Features

* **commands:** run middleware around a command ([#21](https://github.com/Tempcord/framework/issues/21)) ([270afd4](https://github.com/Tempcord/framework/commit/270afd4de13cd9fa2db79f14292b7219b6781b24))

## [0.13.0](https://github.com/Tempcord/framework/compare/v0.12.4...v0.13.0) (2026-09-03)

### Features

* **commands:** let a command be an activity entry point ([8794dbb](https://github.com/Tempcord/framework/commit/8794dbbfda9560fbc66fa06246516d76c6bd6e1c))

## [0.12.4](https://github.com/Tempcord/framework/compare/v0.12.3...v0.12.4) (2026-09-03)

### Bug Fixes

* **commands:** give a handler the interaction it asks for by type ([cbe23ee](https://github.com/Tempcord/framework/commit/cbe23eead12b512e9165e0a2f44de26866495481))

## [0.12.3](https://github.com/Tempcord/framework/compare/v0.12.2...v0.12.3) (2026-09-03)

### Bug Fixes

* **logging:** keep a console channel at its configured level ([d03eaad](https://github.com/Tempcord/framework/commit/d03eaad259970f8809a01376ac7859ca514f497a))

## [0.12.2](https://github.com/Tempcord/framework/compare/v0.12.1...v0.12.2) (2026-09-03)

### Bug Fixes

* **commands:** name an option after its parameter in snake_case ([f773d7b](https://github.com/Tempcord/framework/commit/f773d7b030a3da0531597734576d343dabc1a0dd))

## [0.12.1](https://github.com/Tempcord/framework/compare/v0.12.0...v0.12.1) (2026-09-02)

### Bug Fixes

* **discovery:** keep command definitions exportable so discovery can cache ([860d959](https://github.com/Tempcord/framework/commit/860d959ac83f650a60bec84ab97fc62185bd2c66))

## [0.12.0](https://github.com/Tempcord/framework/compare/v0.11.0...v0.12.0) (2026-09-02)

### Features

* **commands:** take a backed enum as a command option ([#20](https://github.com/Tempcord/framework/issues/20)) ([326704b](https://github.com/Tempcord/framework/commit/326704b14595b0c0eec80a7b1f2972525a823cf0))

## [0.11.0](https://github.com/Tempcord/framework/compare/v0.10.0...v0.11.0) (2026-09-02)

### Features

* **messaging:** write to a member without risking the caller ([#17](https://github.com/Tempcord/framework/issues/17)) ([ccfa40f](https://github.com/Tempcord/framework/commit/ccfa40faaa4f2f53d6eea5fa3fc9cfe9e58bbf3f))

### Reverts

* take scheduling back out of the core ([#18](https://github.com/Tempcord/framework/issues/18)) ([63bd82e](https://github.com/Tempcord/framework/commit/63bd82e3e31d83744400dae897c4416fab79c29d)), closes [#16](https://github.com/Tempcord/framework/issues/16)

## [0.10.0](https://github.com/Tempcord/framework/compare/v0.9.0...v0.10.0) (2026-09-02)

### Features

* **scheduling:** declare recurring work with #[Scheduled] ([#16](https://github.com/Tempcord/framework/issues/16)) ([7e5e5c4](https://github.com/Tempcord/framework/commit/7e5e5c4298f786c55115dc5c5ece0a4144edc6ec))

## [0.9.0](https://github.com/Tempcord/framework/compare/v0.8.0...v0.9.0) (2026-09-02)

### Features

* **commands:** hand a context menu what it was used on ([#15](https://github.com/Tempcord/framework/issues/15)) ([9910f0e](https://github.com/Tempcord/framework/commit/9910f0e761a1ed34e5c46a5c74ebb8fdbfa31394))

## [0.8.0](https://github.com/Tempcord/framework/compare/v0.7.0...v0.8.0) (2026-09-01)

### Features

* add component routing, a gateway cache and container-built autocompletes ([#9](https://github.com/Tempcord/framework/issues/9)) ([245574b](https://github.com/Tempcord/framework/commit/245574bee07530c491c0e7404f6edd3d72a13569)), closes [Tempcord/discord-php#4](https://github.com/Tempcord/discord-php/issues/4)
