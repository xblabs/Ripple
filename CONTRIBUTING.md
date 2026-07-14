# Contributing

Thanks for considering a contribution to Ripple.

## Getting started

```bash
git clone https://github.com/xblabs/ripple.git
cd ripple
composer install
```

## Development scripts

| Command             | What it does                                     |
|---------------------|--------------------------------------------------|
| `composer test`     | Run the PHPUnit test suite                       |
| `composer analyse`  | Run PHPStan (level 8) over `src`                 |
| `composer cs`       | Check code style with PHP-CS-Fixer (dry run)     |
| `composer cs:fix`   | Apply code-style fixes                           |
| `composer ci`       | Run tests, static analysis and the style check   |

## Conventions

- **PHP 8.1+.** Keep the code compatible with the whole supported range (8.1–8.4).
- **Code style** is enforced by `.php-cs-fixer.dist.php` — tab indentation with a single space inside parentheses
  (`dispatch( 'test' )`). Run `composer cs:fix` before committing.
- **Static analysis** must stay green at PHPStan level 8 for `src`.
- **Tests** accompany behaviour changes. Every bug fix should include a test that fails before the fix. Test methods use
  `snake_case` names.
- All source files declare `strict_types=1`.

## Pull request checklist

- [ ] `composer ci` passes locally
- [ ] New/changed behaviour is covered by tests
- [ ] `CHANGELOG.md` updated for user-facing changes
- [ ] Public API changes reflected in `README.md` (and `UPGRADE.md` if breaking)
