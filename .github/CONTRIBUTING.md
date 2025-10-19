# Contributing to POBIS

Thank you for your interest in contributing to POBIS! This document provides guidelines and information for contributors.

## 🚀 Getting Started

### Prerequisites
- PHP 8.2+
- Composer
- Node.js & npm
- Docker & Docker Compose
- Git

### Development Setup
1. Fork the repository
2. Clone your fork: `git clone https://github.com/your-username/pobis.git`
3. Copy environment file: `cp .env.example .env`
4. Configure your `.env` file (especially Microsoft OAuth settings)
5. Start with Docker: `docker compose up -d`
6. Install dependencies: `docker exec pobis composer install`
7. Run migrations: `docker exec pobis php artisan migrate`
8. Seed database: `docker exec pobis php artisan db:seed`

## 🔄 Development Workflow

### Branch Naming
- `feature/description` - New features
- `bugfix/description` - Bug fixes
- `hotfix/description` - Critical fixes
- `docs/description` - Documentation updates
- `oauth/description` - Microsoft OAuth related changes

### Commit Messages
Follow conventional commit format:
```
type(scope): description

[optional body]

[optional footer]
```

**Types:**
- `feat`: New feature
- `fix`: Bug fix
- `docs`: Documentation
- `style`: Code style changes
- `refactor`: Code refactoring
- `test`: Adding tests
- `chore`: Maintenance tasks
- `oauth`: Microsoft OAuth changes
- `i18n`: Localization changes

**Examples:**
- `feat(auth): add Microsoft OAuth integration`
- `fix(oauth): resolve token refresh issue`
- `docs(setup): update Microsoft authentication guide`

## 🧪 Testing

### Running Tests
```bash
# Run all tests
docker exec pobis php artisan test

# Run specific test suite
docker exec pobis php artisan test --testsuite=Feature

# Run with coverage
docker exec pobis php artisan test --coverage
```

### Test Requirements
- All new features must include tests
- Bug fixes should include regression tests
- Microsoft OAuth changes require integration tests
- Maintain test coverage above 80%

### Manual Testing Checklist
- [ ] Test in multiple browsers
- [ ] Test Microsoft OAuth flow
- [ ] Test user role restrictions
- [ ] Test Lithuanian name gender detection
- [ ] Test account linking/unlinking
- [ ] Test password restrictions for MS accounts

## 🎨 Code Standards

### PHP Standards
- Follow PSR-12 coding standards
- Use meaningful variable and method names
- Add PHPDoc comments for public methods
- Keep methods focused and small

### Laravel Conventions
- Use Eloquent relationships properly
- Follow Laravel naming conventions
- Use form requests for validation
- Implement proper authorization

### Frontend Standards
- Use Tailwind CSS classes
- Follow Blade template conventions
- Ensure responsive design
- Test accessibility

### Microsoft OAuth Specific
- Always validate tokens
- Handle errors gracefully
- Log OAuth operations
- Respect user privacy
- Follow Microsoft Graph API best practices

## 🌐 Localization

### Lithuanian Language Support
- All user-facing text must be localized
- Use `__()` helper for translations
- Add entries to `resources/lang/lt.json`
- Test both English and Lithuanian interfaces

### Adding New Translations
1. Add English text with `__('key')`
2. Add Lithuanian translation to `lt.json`
3. Test both languages
4. Update documentation if needed

## 🔒 Security Guidelines

### General Security
- Never commit secrets or API keys
- Validate all user input
- Use CSRF protection
- Implement proper authorization
- Follow OWASP guidelines

### Microsoft OAuth Security
- Validate state tokens
- Secure token storage
- Implement proper scopes
- Handle token expiration
- Log security events

## 📋 Pull Request Process

### Before Submitting
1. Run code quality checks: `composer run-script cs-check`
2. Run all tests: `php artisan test`
3. Update documentation if needed
4. Test Microsoft OAuth functionality
5. Check Lithuanian translations

### PR Requirements
- [ ] Descriptive title and description
- [ ] Link to related issue
- [ ] Tests included/updated
- [ ] Documentation updated
- [ ] Code quality checks pass
- [ ] No merge conflicts
- [ ] Screenshots for UI changes

### Review Process
1. Automated checks must pass
2. Code review by maintainer
3. Manual testing if needed
4. Approval and merge

## 🐛 Bug Reports

### Information to Include
- Clear description of the issue
- Steps to reproduce
- Expected vs actual behavior
- Environment details
- Error messages/logs
- Screenshots if applicable

### Microsoft OAuth Issues
- Include specific OAuth error codes
- Mention Microsoft account type
- Check Azure app registration
- Provide tenant information (if safe)

## 💡 Feature Requests

### Proposal Format
- Problem statement
- Proposed solution
- User stories
- Technical considerations
- Impact assessment

### Microsoft OAuth Features
- Consider security implications
- Check Microsoft Graph API capabilities
- Evaluate user privacy impact
- Plan for different account types

## 🏷️ Issue Labels

- `bug` - Something isn't working
- `enhancement` - New feature or request
- `documentation` - Documentation improvements
- `good first issue` - Good for newcomers
- `help wanted` - Extra attention needed
- `microsoft-oauth` - OAuth related issues
- `authentication` - Auth/login issues
- `database` - Database related
- `frontend` - UI/UX issues
- `backend` - Server-side issues
- `security` - Security related
- `performance` - Performance issues
- `i18n` - Internationalization

## 📞 Getting Help

- 📚 Check the [documentation](./README.md)
- 💬 Join [discussions](https://github.com/your-username/pobis/discussions)
- 🐛 Search [existing issues](https://github.com/your-username/pobis/issues)
- 📧 Contact maintainers for security issues

## 📜 Code of Conduct

- Be respectful and inclusive
- Focus on constructive feedback
- Help others learn and grow
- Follow professional standards
- Report inappropriate behavior

## 🙏 Recognition

Contributors will be recognized in:
- README.md contributors section
- Release notes for significant contributions
- Special thanks for major features

---

Thank you for contributing to POBIS! Your efforts help make this project better for everyone. 🎉
