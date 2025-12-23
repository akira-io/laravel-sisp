# Contributing to Laravel SISP

Thank you for considering contributing to Laravel SISP! This document outlines the process and guidelines for contributing.

## Code of Conduct

Be respectful and considerate of others. We are committed to providing a welcoming and inclusive environment for all contributors.

## How to Contribute

### Reporting Bugs

Before creating bug reports, please check existing issues to avoid duplicates. When creating a bug report, include:

- Clear and descriptive title
- Detailed steps to reproduce the issue
- Expected behavior vs actual behavior
- Laravel and PHP version information
- Code samples or test cases if applicable
- Stack traces or error messages

### Suggesting Enhancements

Enhancement suggestions are tracked as GitHub issues. When creating an enhancement suggestion:

- Use a clear and descriptive title
- Provide a detailed description of the proposed functionality
- Explain why this enhancement would be useful
- Include code examples if applicable

### Pull Requests

1. Fork the repository and create your branch from `main`
2. Write clear, descriptive commit messages
3. Ensure all tests pass before submitting
4. Add tests for new functionality
5. Update documentation as needed
6. Follow the existing code style

## Development Setup

### Prerequisites

- PHP 8.4 or higher
- Composer
- Laravel 12 or higher

### Installation

```bash
git clone https://github.com/akira-io/laravel-sisp.git
cd laravel-sisp
composer install
```

### Running Tests

```bash
# Run full test suite
composer test

# Run specific test types
composer test:arch       # Architecture tests
composer test:types      # Type coverage
composer test:coverage   # Code coverage
composer test:lint       # Code style
```

## Coding Standards

### Code Style

This package follows PSR-12 coding standards and uses Laravel Pint for automatic formatting:

```bash
composer lint          # Format code
composer test:lint     # Check code style
```

### Type Safety

- All code must include strict type declarations
- Use PHP 8.4 type hints for parameters and return types
- Maintain 100% type coverage
- Avoid mixed types where possible

### Architecture

- Follow SOLID principles
- Use action classes for business logic
- Keep controllers thin
- Use value objects for data transfer
- Immutability where practical (readonly classes)

### Testing

- Write tests for all new functionality
- Maintain 100% code coverage
- Use Pest for testing
- Follow AAA pattern (Arrange, Act, Assert)
- Test both happy paths and edge cases

## Code Review Process

1. All submissions require review before merging
2. Reviewers will check for:
   - Code quality and style
   - Test coverage
   - Documentation completeness
   - Backward compatibility
3. Address review feedback promptly
4. Keep pull requests focused and atomic

## Commit Guidelines

### Commit Message Format

```
<type>: <subject>

<body>

<footer>
```

### Types

- `feat`: New feature
- `fix`: Bug fix
- `docs`: Documentation changes
- `style`: Code style changes (formatting)
- `refactor`: Code refactoring
- `test`: Adding or updating tests
- `chore`: Maintenance tasks

### Examples

```
feat: add support for partial refunds

Implement partial refund functionality allowing merchants
to refund specific transaction items.

Closes #123
```

```
fix: resolve race condition in callback handling

Add unique constraint and duplicate detection to prevent
processing the same callback twice.

Fixes #456
```

## Documentation

- Update documentation for new features
- Keep examples accurate and tested
- Use clear, concise language
- Include code examples where helpful

## Branch Naming

- `feature/description` - New features
- `fix/description` - Bug fixes
- `docs/description` - Documentation updates
- `refactor/description` - Code refactoring

## Release Process

Maintainers will:

1. Review and merge approved pull requests
2. Update CHANGELOG.md
3. Tag releases following semantic versioning
4. Publish releases to Packagist

## Questions?

- Open a GitHub discussion for questions
- Check existing issues and documentation
- Join community channels if available

## License

By contributing, you agree that your contributions will be licensed under the MIT License.
