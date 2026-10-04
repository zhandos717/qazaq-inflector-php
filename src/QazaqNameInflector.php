<?php

declare(strict_types=1);

namespace Zhandos\QazaqInflector;

use InvalidArgumentException;

/**
 * Склонение казахских имён, ФИО и местоимений: суффиксы падежей по гармонии гласных
 * и классу последнего звука основы.
 *
 * Падежи: nominative (атау), genitive (ілік), dative (барыс), accusative (табыс),
 * locative (жатыс), ablative (шығыс), instrumental (көмектес).
 *
 * - ФИО: части, оканчивающиеся на «ұлы» или «қызы», не склоняются.
 * - В ФИО через пробел склоняется каждая часть, в двойном имени через дефис — только последняя.
 * - Местоимения (мен, сен, сіз, ол, біз, сендер, сіздер, олар) имеют специальные формы.
 */
final class QazaqNameInflector
{
    public const NOMINATIVE = 'nominative';
    public const GENITIVE = 'genitive';
    public const DATIVE = 'dative';
    public const ACCUSATIVE = 'accusative';
    public const LOCATIVE = 'locative';
    public const ABLATIVE = 'ablative';
    public const INSTRUMENTAL = 'instrumental';

    public const CASES = [
        self::NOMINATIVE, self::GENITIVE, self::DATIVE, self::ACCUSATIVE,
        self::LOCATIVE, self::ABLATIVE, self::INSTRUMENTAL,
    ];

    public const PERSON_1SG = '1sg';
    public const PERSON_2SG = '2sg';
    public const PERSON_2SG_FORMAL = '2sg_formal';
    public const PERSON_1PL = '1pl';
    public const PERSON_2PL = '2pl';
    public const PERSON_2PL_FORMAL = '2pl_formal';
    public const PERSON_3 = '3';

    // и/у не задают твёрдость: они встречаются в словах обоих рядов
    private const HARD_VOWELS = 'аұыояюё';
    private const SOFT_VOWELS = 'әүіөеэ';
    // и/у на конце звучат как [ій]/[ұу] и присоединяют суффиксы как сонорные согласные
    private const VOWELS = 'аәеэоөұүыіяюё';
    private const NASALS = 'мнң';
    private const VOICED_SIBILANTS = 'жз';
    // б, в, г, д на конце заимствований оглушаются: Құнанбаевқа, Ахмедке
    private const VOICELESS = 'кқпстфхцчшщбвгд';

    // Перед гласной глухие п, к, қ озвончаются: кітап → кітабы, Сұлтанбек → Сұлтанбегі
    private const VOICING = ['п' => 'б', 'к' => 'г', 'қ' => 'ғ', 'П' => 'Б', 'К' => 'Г', 'Қ' => 'Ғ'];

    private const PATRONYMIC_SUFFIXES = ['ұлы', 'қызы'];
    // Гармонию мужских фамилий определяет основа: Құнанбаев → Құнанба-, Ахметов → Ахмет-.
    // В женских -ова/-ева гармонию задаёт конечная -а: Ахметоваға
    private const SURNAME_SUFFIXES = ['ов', 'ев'];

    private const PRONOUNS = [
        'мен' => [
            'nominative' => 'мен', 'genitive' => 'менің', 'dative' => 'маған', 'accusative' => 'мені',
            'locative' => 'менде', 'ablative' => 'менен', 'instrumental' => 'менімен',
        ],
        'біз' => [
            'nominative' => 'біз', 'genitive' => 'біздің', 'dative' => 'бізге', 'accusative' => 'бізді',
            'locative' => 'бізде', 'ablative' => 'бізден', 'instrumental' => 'бізбен',
        ],
        'сен' => [
            'nominative' => 'сен', 'genitive' => 'сенің', 'dative' => 'саған', 'accusative' => 'сені',
            'locative' => 'сенде', 'ablative' => 'сенен', 'instrumental' => 'сенімен',
        ],
        'сіз' => [
            'nominative' => 'сіз', 'genitive' => 'сіздің', 'dative' => 'сізге', 'accusative' => 'сізді',
            'locative' => 'сізде', 'ablative' => 'сізден', 'instrumental' => 'сізбен',
        ],
        'ол' => [
            'nominative' => 'ол', 'genitive' => 'оның', 'dative' => 'оған', 'accusative' => 'оны',
            'locative' => 'онда', 'ablative' => 'одан', 'instrumental' => 'онымен',
        ],
        'сендер' => [
            'nominative' => 'сендер', 'genitive' => 'сендердің', 'dative' => 'сендерге', 'accusative' => 'сендерді',
            'locative' => 'сендерде', 'ablative' => 'сендерден', 'instrumental' => 'сендермен',
        ],
        'сіздер' => [
            'nominative' => 'сіздер', 'genitive' => 'сіздердің', 'dative' => 'сіздерге', 'accusative' => 'сіздерді',
            'locative' => 'сіздерде', 'ablative' => 'сіздерден', 'instrumental' => 'сіздермен',
        ],
        'олар' => [
            'nominative' => 'олар', 'genitive' => 'олардың', 'dative' => 'оларға', 'accusative' => 'оларды',
            'locative' => 'оларда', 'ablative' => 'олардан', 'instrumental' => 'олармен',
        ],
    ];

    // Классы окончаний: vowel, nasal (м н ң), sonorant (л р й у и), voiced (ж з), voiceless
    private const CASE_SUFFIXES = [
        'genitive' => [
            'vowel' => ['ның', 'нің'], 'nasal' => ['ның', 'нің'], 'sonorant' => ['дың', 'дің'],
            'voiced' => ['дың', 'дің'], 'voiceless' => ['тың', 'тің'],
        ],
        'dative' => [
            'vowel' => ['ға', 'ге'], 'nasal' => ['ға', 'ге'], 'sonorant' => ['ға', 'ге'],
            'voiced' => ['ға', 'ге'], 'voiceless' => ['қа', 'ке'],
        ],
        'accusative' => [
            'vowel' => ['ны', 'ні'], 'nasal' => ['ды', 'ді'], 'sonorant' => ['ды', 'ді'],
            'voiced' => ['ды', 'ді'], 'voiceless' => ['ты', 'ті'],
        ],
        'locative' => [
            'vowel' => ['да', 'де'], 'nasal' => ['да', 'де'], 'sonorant' => ['да', 'де'],
            'voiced' => ['да', 'де'], 'voiceless' => ['та', 'те'],
        ],
        'ablative' => [
            'vowel' => ['дан', 'ден'], 'nasal' => ['нан', 'нен'], 'sonorant' => ['дан', 'ден'],
            'voiced' => ['дан', 'ден'], 'voiceless' => ['тан', 'тен'],
        ],
        'instrumental' => [
            'vowel' => ['мен', 'мен'], 'nasal' => ['мен', 'мен'], 'sonorant' => ['мен', 'мен'],
            'voiced' => ['бен', 'бен'], 'voiceless' => ['пен', 'пен'],
        ],
    ];

    // [после гласной, после согласной], каждый вариант — [твёрдый, мягкий]
    private const POSSESSIVE_SUFFIXES = [
        '1sg' => [['м', 'м'], ['ым', 'ім']],
        '2sg' => [['ң', 'ң'], ['ың', 'ің']],
        '2sg_formal' => [['ңыз', 'ңіз'], ['ыңыз', 'іңіз']],
        '1pl' => [['мыз', 'міз'], ['ымыз', 'іміз']],
        '3' => [['сы', 'сі'], ['ы', 'і']],
    ];

    private const PRONOUN_PERSONS = [
        'мен' => '1sg', 'сен' => '2sg', 'сіз' => '2sg_formal', 'біз' => '1pl',
        'сендер' => '2pl', 'сіздер' => '2pl_formal', 'ол' => '3', 'олар' => '3',
    ];

    // Жіктік жалғау: [после гласной/сонорной, после ж/з, после глухой], каждый — [твёрдый, мягкий]
    private const PREDICATE_SUFFIXES = [
        '1sg' => [['мын', 'мін'], ['бын', 'бін'], ['пын', 'пін']],
        '1pl' => [['мыз', 'міз'], ['быз', 'біз'], ['пыз', 'піз']],
        '2sg' => [['сың', 'сің'], ['сың', 'сің'], ['сың', 'сің']],
        '2sg_formal' => [['сыз', 'сіз'], ['сыз', 'сіз'], ['сыз', 'сіз']],
        '2pl' => [['сыңдар', 'сіңдер'], ['сыңдар', 'сіңдер'], ['сыңдар', 'сіңдер']],
        '2pl_formal' => [['сыздар', 'сіздер'], ['сыздар', 'сіздер'], ['сыздар', 'сіздер']],
        '3' => [['', ''], ['', ''], ['', '']],
    ];

    // Падежи после притяжательного суффикса 3-го лица идут через вставное -н-: Арнасына, Нұрланын
    private const PRONOMINAL_CASE_SUFFIXES = [
        'genitive' => ['ның', 'нің'], 'dative' => ['на', 'не'], 'accusative' => ['н', 'н'],
        'locative' => ['нда', 'нде'], 'ablative' => ['нан', 'нен'], 'instrumental' => ['мен', 'мен'],
    ];

    private const PLURAL_SUFFIXES = [
        'vowel' => ['лар', 'лер'], 'sonorant' => ['лар', 'лер'], 'nasal' => ['дар', 'дер'],
        'voiced' => ['дар', 'дер'], 'voiceless' => ['тар', 'тер'],
    ];

    /**
     * @param bool $strict InvalidArgumentException на неизвестный падеж или лицо вместо возврата слова без изменений
     */
    public function __construct(private readonly bool $strict = false)
    {
    }

    /**
     * Склоняет имя, ФИО или местоимение. Неизвестный падеж: слово как есть или исключение в strict.
     */
    public function inflect(?string $name, string $case): ?string
    {
        if ($name === null) {
            return null;
        }
        $word = trim($name);
        $case = mb_strtolower($case);
        if ($word === '' || $case === self::NOMINATIVE) {
            return $word;
        }

        $pronoun = self::PRONOUNS[mb_strtolower($word)] ?? null;
        if ($pronoun !== null) {
            $form = $pronoun[$case] ?? null;
            if ($form === null) {
                return $this->unknown($word, "case '{$case}'");
            }

            return self::isUpperChar(mb_substr($word, 0, 1)) ? self::capitalize($form) : $form;
        }

        if (preg_match('/\s/u', $word)) {
            $parts = preg_split('/\s+/u', $word) ?: [];

            return implode(' ', array_map(
                fn (string $part): string => self::endsWithAny(mb_strtolower($part), self::PATRONYMIC_SUFFIXES)
                    ? $part
                    : (string) $this->inflect($part, $case),
                $parts,
            ));
        }

        if (str_contains($word, '-')) {
            $pos = (int) strrpos($word, '-');

            return substr($word, 0, $pos) . '-' . $this->inflect(substr($word, $pos + 1), $case);
        }

        return $this->inflectWord($word, $case);
    }

    /**
     * Притяжательная форма с падежом: possessive('Арна', '1sg', 'dative') → 'Арнама'.
     *
     * $person: '1sg' (менің), '2sg' (сенің), '2sg_formal' (сіздің), '1pl' (біздің),
     * '2pl' (сендердің), '2pl_formal' (сіздердің), '3' (оның/олардың).
     * $plural — несколько обладаемых: Арналарым, Нұрландары.
     * В ФИО форму принимает только последняя часть.
     */
    public function possessive(
        string $name,
        string $person = self::PERSON_3,
        string $case = self::NOMINATIVE,
        bool $plural = false,
    ): string {
        $word = trim($name);
        $case = mb_strtolower($case);
        // 2-е лицо мн. ч. владельца = -лар/-лер + суффикс 2-го лица ед. ч.: үйлерің, үйлеріңіз
        $ownerPlural = in_array($person, [self::PERSON_2PL, self::PERSON_2PL_FORMAL], true);
        $basePerson = [self::PERSON_2PL => self::PERSON_2SG, self::PERSON_2PL_FORMAL => self::PERSON_2SG_FORMAL][$person]
            ?? $person;
        $variants = self::POSSESSIVE_SUFFIXES[$basePerson] ?? null;
        if ($word === '') {
            return $word;
        }
        if ($variants === null) {
            return $this->unknown($word, "person '{$person}'");
        }

        $separator = preg_match('/\s/u', $word) ? ' ' : (str_contains($word, '-') ? '-' : null);
        if ($separator !== null) {
            $pos = (int) strrpos($word, $separator);

            return substr($word, 0, $pos) . $separator
                . $this->possessive(substr($word, $pos + 1), $person, $case, $plural);
        }

        if ($plural || $ownerPlural) {
            $word = $this->pluralize($word);
        }

        $base = $this->endingClass($word) === 'vowel'
            ? $this->addSuffix($word, $variants[0])
            : $this->addSuffix($this->voice($word), $variants[1]);
        if ($case === self::NOMINATIVE) {
            return $base;
        }

        if ($basePerson === self::PERSON_3) {
            $suffixes = self::PRONOMINAL_CASE_SUFFIXES[$case] ?? null;

            return $suffixes === null ? $this->unknown($base, "case '{$case}'") : $this->addSuffix($base, $suffixes);
        }
        // После притяжательных -м/-ң барыс септік теряет начальный согласный: Арнама, Арнаңа
        if ($case === self::DATIVE && in_array($basePerson, [self::PERSON_1SG, self::PERSON_2SG], true)) {
            return $this->addSuffix($base, ['а', 'е']);
        }

        return $this->inflectWord($base, $case);
    }

    /**
     * Изафет «чей-то что-то»: genitivePhrase('Нұрлан', 'әке') → 'Нұрланның әкесі'.
     * Местоимение-владелец задаёт лицо: genitivePhrase('мен', 'кітап') → 'менің кітабым'.
     */
    public function genitivePhrase(
        string $owner,
        string $thing,
        string $case = self::NOMINATIVE,
        bool $plural = false,
    ): string {
        $ownerWord = trim($owner);
        $person = self::PRONOUN_PERSONS[mb_strtolower($ownerWord)] ?? self::PERSON_3;

        return $this->inflect($ownerWord, self::GENITIVE) . ' ' . $this->possessive($thing, $person, $case, $plural);
    }

    /**
     * Сказуемое с личным окончанием: predicate('студент', '1sg') → 'студентпін'.
     * Лицо '3' (ол/олар) окончания не добавляет.
     */
    public function predicate(string $word, string $person): string
    {
        $stripped = trim($word);
        $variants = self::PREDICATE_SUFFIXES[$person] ?? null;
        if ($stripped === '') {
            return $stripped;
        }
        if ($variants === null) {
            return $this->unknown($stripped, "person '{$person}'");
        }
        $index = match ($this->endingClass($stripped)) {
            'voiced' => 1,
            'voiceless' => 2,
            default => 0,
        };

        return $this->addSuffix($stripped, $variants[$index]);
    }

    /**
     * Множественное число: -лар/-лер, -дар/-дер или -тар/-тер.
     */
    public function pluralize(string $name): string
    {
        $word = trim($name);
        if ($word === '') {
            return $word;
        }
        if (mb_strtolower(mb_substr($word, -1)) === 'л') {
            return $this->addSuffix($word, ['дар', 'дер']);
        }

        return $this->addSuffix($word, self::PLURAL_SUFFIXES[$this->endingClass($word)]);
    }

    /**
     * Все падежи для имени: [падеж => [ед. ч., мн. ч.]].
     *
     * @return array<string, array{0: string, 1: string}>
     */
    public function declension(string $name): array
    {
        $plural = $this->pluralize($name);
        $result = [];
        foreach (self::CASES as $case) {
            $result[$case] = [(string) $this->inflect($name, $case), (string) $this->inflect($plural, $case)];
        }

        return $result;
    }

    private function inflectWord(string $word, string $case): string
    {
        $suffixes = self::CASE_SUFFIXES[$case] ?? null;
        if ($suffixes === null) {
            return $this->unknown($word, "case '{$case}'");
        }

        return $this->addSuffix($word, $suffixes[$this->endingClass($word)]);
    }

    private function isHard(string $word): bool
    {
        $lower = mb_strtolower($word);
        foreach (self::SURNAME_SUFFIXES as $suffix) {
            $stem = mb_substr($lower, 0, mb_strlen($lower) - mb_strlen($suffix));
            if (str_ends_with($lower, $suffix) && self::containsAny($stem, self::HARD_VOWELS . self::SOFT_VOWELS)) {
                $lower = $stem;
                break;
            }
        }
        $chars = mb_str_split($lower);
        for ($i = count($chars) - 1; $i >= 0; $i--) {
            if (str_contains(self::HARD_VOWELS, $chars[$i])) {
                return true;
            }
            if (str_contains(self::SOFT_VOWELS, $chars[$i])) {
                return false;
            }
        }

        return true;
    }

    private function endingClass(string $word): string
    {
        $last = mb_strtolower(mb_substr($word, -1));

        return match (true) {
            str_contains(self::VOWELS, $last) => 'vowel',
            str_contains(self::NASALS, $last) => 'nasal',
            str_contains(self::VOICED_SIBILANTS, $last) => 'voiced',
            str_contains(self::VOICELESS, $last) => 'voiceless',
            default => 'sonorant',
        };
    }

    /**
     * @param array{0: string, 1: string} $variants
     */
    private function addSuffix(string $word, array $variants): string
    {
        $suffix = $this->isHard($word) ? $variants[0] : $variants[1];

        return $word . (self::isUpperWord($word) && mb_strlen($word) > 1 ? mb_strtoupper($suffix) : $suffix);
    }

    private function voice(string $word): string
    {
        $last = mb_substr($word, -1);

        return mb_substr($word, 0, -1) . (self::VOICING[$last] ?? $last);
    }

    private function unknown(string $word, string $what): string
    {
        if ($this->strict) {
            throw new InvalidArgumentException("Unknown {$what}");
        }

        return $word;
    }

    private static function isUpperChar(string $char): bool
    {
        return $char !== mb_strtolower($char);
    }

    private static function isUpperWord(string $word): bool
    {
        return $word === mb_strtoupper($word) && $word !== mb_strtolower($word);
    }

    private static function capitalize(string $word): string
    {
        return mb_strtoupper(mb_substr($word, 0, 1)) . mb_substr($word, 1);
    }

    /**
     * @param list<string> $suffixes
     */
    private static function endsWithAny(string $word, array $suffixes): bool
    {
        foreach ($suffixes as $suffix) {
            if (str_ends_with($word, $suffix)) {
                return true;
            }
        }

        return false;
    }

    private static function containsAny(string $haystack, string $chars): bool
    {
        foreach (mb_str_split($chars) as $char) {
            if (str_contains($haystack, $char)) {
                return true;
            }
        }

        return false;
    }
}
