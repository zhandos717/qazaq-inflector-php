# Changelog

## [0.5.0] - 2026-10-05

First PHP release, a port of [qazaq_inflector 0.5.0](https://github.com/zhandos717/qazaq_inflector) for Python.

### Added
- `inflect()` — seven cases for names, full names and personal pronouns.
- `pluralize()`, `declension()`.
- `possessive()` — all persons, with case endings and plural possessed items.
- `genitivePhrase()` — `Нұрланның әкесі`, `менің кітабым`.
- `predicate()` — personal predicate endings: `студентпін`, `Нұрлансың`.
- Strict mode: `new QazaqNameInflector(strict: true)` throws `InvalidArgumentException` on an unknown case or person.
- Test suite checks byte-for-byte parity with the Python version on 8,700 forms.

[0.5.0]: https://github.com/zhandos717/qazaq-inflector-php/releases/tag/v0.5.0
