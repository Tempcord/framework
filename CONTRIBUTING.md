# Contributing to Tempcord

Thank you for considering contributing to Tempcord! We welcome contributions from everyone and are grateful for every contribution, no matter how small.

## Table of Contents

- [Code of Conduct](#code-of-conduct)
- [Getting Started](#getting-started)
- [How to Contribute](#how-to-contribute)
- [Development Setup](#development-setup)
- [Coding Standards](#coding-standards)
- [Testing](#testing)
- [Pull Request Process](#pull-request-process)
- [Reporting Issues](#reporting-issues)
- [Feature Requests](#feature-requests)

## Code of Conduct

This project and everyone participating in it is governed by our [Code of Conduct](CODE_OF_CONDUCT.md). By participating, you are expected to uphold this code.

## Getting Started

### Prerequisites

- PHP 8.2 or higher
- Composer
- Git
- A Discord application and bot token for testing

### Development Setup

1. **Fork the repository**
   ```bash
   # Click the "Fork" button on GitHub, then clone your fork
   git clone https://github.com/your-username/tempcord.git
   cd tempcord
   ```

2. **Install dependencies**
   ```bash
   composer install
   ```

3. **Set up environment**
   ```bash
   cp .env.example .env
   # Edit .env with your Discord bot credentials
   ```

4. **Run tests to ensure everything works**
   ```bash
   composer test
   ```

## How to Contribute

### Types of Contributions

We welcome several types of contributions:

- **Bug fixes**: Help us squash bugs!
- **Feature development**: Add new functionality
- **Documentation**: Improve our docs
- **Testing**: Add or improve tests
- **Code quality**: Refactoring, performance improvements
- **Examples**: Add usage examples or tutorials

### Before You Start

1. **Check existing issues**: Look for existing issues or discussions about your idea
2. **Create an issue**: For significant changes, create an issue first to discuss the approach
3. **Small changes**: For small bug fixes or improvements, you can directly create a PR

## Development Workflow

### Branch Naming

Use descriptive branch names:
- `feature/add-slash-commands`
- `bugfix/fix-memory-leak`
- `docs/update-installation-guide`
- `refactor/improve-event-handling`

### Commit Messages

Follow conventional commit format:
```
type(scope): description

[optional body]

[optional footer]
```

Types:
- `feat`: New feature
- `fix`: Bug fix
- `docs`: Documentation changes
- `style`: Code style changes (formatting, etc.)
- `refactor`: Code refactoring
- `test`: Adding or updating tests
- `chore`: Maintenance tasks

Examples:
```
feat(commands): add support for slash command options
fix(events): resolve memory leak in event listener
docs(readme): update installation instructions
```

## Coding Standards

### PHP Standards

- Follow [PSR-12](https://www.php-fig.org/psr/psr-12/) coding standard
- Use strict types: `declare(strict_types=1);`
- Add type hints for all parameters and return types
- Use meaningful variable and method names
- Write self-documenting code with clear comments when necessary

### Code Style

We use PHP CS Fixer to maintain consistent code style:

```bash
# Check code style
composer format:check

# Fix code style issues
composer format
```

### Static Analysis

We use PHPStan and Psalm for static analysis:

```bash
# Run PHPStan
composer analyse

# Run Psalm
composer psalm
```

## Testing

### Writing Tests

- Write tests for all new functionality
- Maintain or improve test coverage
- Use descriptive test method names
- Follow the AAA pattern (Arrange, Act, Assert)

### Test Structure

```php
<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Tempcord\YourClass;

class YourClassTest extends TestCase
{
    public function test_it_does_something_expected(): void
    {
        // Arrange
        $instance = new YourClass();
        
        // Act
        $result = $instance->doSomething();
        
        // Assert
        $this->assertEquals('expected', $result);
    }
}
```

### Running Tests

```bash
# Run all tests
composer test

# Run tests with coverage
composer test:coverage

# Run specific test file
vendor/bin/phpunit tests/Unit/YourClassTest.php

# Run tests matching a pattern
vendor/bin/phpunit --filter="test_it_does_something"
```

## Pull Request Process

### Before Submitting

1. **Update your branch**
   ```bash
   git checkout main
   git pull upstream main
   git checkout your-feature-branch
   git rebase main
   ```

2. **Run the full test suite**
   ```bash
   composer test
   composer analyse
   composer format:check
   ```

3. **Update documentation** if needed

### PR Guidelines

1. **Clear title and description**: Explain what your PR does and why
2. **Link related issues**: Use "Fixes #123" or "Closes #123"
3. **Small, focused changes**: Keep PRs focused on a single concern
4. **Add tests**: Include tests for new functionality
5. **Update docs**: Update relevant documentation

### PR Template

```markdown
## Description
Brief description of changes

## Type of Change
- [ ] Bug fix
- [ ] New feature
- [ ] Breaking change
- [ ] Documentation update

## Testing
- [ ] Tests pass locally
- [ ] Added tests for new functionality
- [ ] Manual testing completed

## Checklist
- [ ] Code follows project style guidelines
- [ ] Self-review completed
- [ ] Documentation updated
- [ ] No breaking changes (or clearly documented)
```

## Reporting Issues

### Bug Reports

When reporting bugs, please include:

1. **Clear title**: Summarize the issue
2. **Environment details**: PHP version, OS, Tempcord version
3. **Steps to reproduce**: Detailed steps to reproduce the issue
4. **Expected behavior**: What should happen
5. **Actual behavior**: What actually happens
6. **Code samples**: Minimal code to reproduce the issue
7. **Error messages**: Full error messages and stack traces

### Bug Report Template

```markdown
**Environment:**
- PHP Version: 8.2.x
- Tempcord Version: x.x.x
- OS: macOS/Linux/Windows

**Description:**
A clear description of the bug.

**Steps to Reproduce:**
1. Step one
2. Step two
3. Step three

**Expected Behavior:**
What should happen

**Actual Behavior:**
What actually happens

**Code Sample:**
```php
// Minimal code to reproduce
```

**Error Messages:**
```
Full error message and stack trace
```

## Feature Requests

For feature requests, please:

1. **Check existing issues**: Ensure the feature hasn't been requested
2. **Describe the problem**: What problem does this solve?
3. **Propose a solution**: How should it work?
4. **Consider alternatives**: What other approaches could work?
5. **Provide examples**: Show how it would be used

## Documentation

### Writing Documentation

- Use clear, concise language
- Include code examples
- Test all code examples
- Follow existing documentation style
- Update table of contents if needed

### Documentation Structure

```
docs/
├── getting-started.md
├── commands.md
├── events.md
├── middleware.md
├── configuration.md
└── api-reference.md
```

## Community

### Getting Help

- **GitHub Discussions**: For questions and general discussion
- **Issues**: For bug reports and feature requests
- **Discord**: Join our community Discord server

### Recognition

We recognize contributors in several ways:
- Contributors list in README
- Release notes mention significant contributions
- Special recognition for major contributions

## Release Process

### Versioning

We follow [Semantic Versioning](https://semver.org/):
- **MAJOR**: Breaking changes
- **MINOR**: New features (backward compatible)
- **PATCH**: Bug fixes (backward compatible)

### Release Checklist

1. Update CHANGELOG.md
2. Update version in composer.json
3. Create release tag
4. Update documentation
5. Announce release

## Questions?

If you have questions about contributing, please:

1. Check this document first
2. Search existing issues and discussions
3. Create a new discussion or issue
4. Reach out on Discord

Thank you for contributing to Tempcord! 🎉