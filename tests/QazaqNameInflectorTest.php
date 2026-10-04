<?php

declare(strict_types=1);

namespace Zhandos\QazaqInflector\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Zhandos\QazaqInflector\QazaqNameInflector as Q;

final class QazaqNameInflectorTest extends TestCase
{
    public static function caseExamples(): iterable
    {
        yield ['Нұрлан', Q::GENITIVE, 'Нұрланның'];
        yield ['Нұрлан', Q::LOCATIVE, 'Нұрланда'];
        yield ['Нұрлан', Q::ABLATIVE, 'Нұрланнан'];
        yield ['Сәуле', Q::ACCUSATIVE, 'Сәулені'];
        yield ['Әли', Q::GENITIVE, 'Әлидің'];
        yield ['Айбаз', Q::INSTRUMENTAL, 'Айбазбен'];
        yield ['Бақыт', Q::DATIVE, 'Бақытқа'];
        yield ['Құнанбаев', Q::DATIVE, 'Құнанбаевқа'];
        yield ['Ахметова', Q::DATIVE, 'Ахметоваға'];
        yield ['Абай Құнанбаев', Q::DATIVE, 'Абайға Құнанбаевқа'];
        yield ['Ахмет Байтұрсынұлы', Q::GENITIVE, 'Ахметтің Байтұрсынұлы'];
        yield ['Гүлнар-Баян', Q::LOCATIVE, 'Гүлнар-Баянда'];
        yield ['Мен', Q::ABLATIVE, 'Менен'];
        yield ['олар', Q::DATIVE, 'оларға'];
        yield ['НҰРЛАН', Q::GENITIVE, 'НҰРЛАННЫҢ'];
        yield ['  Нұрлан  ', Q::GENITIVE, 'Нұрланның'];
    }

    #[DataProvider('caseExamples')]
    public function testInflect(string $word, string $case, string $expected): void
    {
        self::assertSame($expected, (new Q())->inflect($word, $case));
    }

    public function testEmptyAndNull(): void
    {
        $inflector = new Q();
        self::assertSame('', $inflector->inflect('', Q::GENITIVE));
        self::assertNull($inflector->inflect(null, Q::DATIVE));
        self::assertSame('', $inflector->possessive('', '4'));
    }

    public function testUnknownCaseReturnsWord(): void
    {
        self::assertSame('Нұрлан', (new Q())->inflect('Нұрлан', 'unknown'));
    }

    public function testPossessiveAndPhrases(): void
    {
        $inflector = new Q();
        self::assertSame('Арнама', $inflector->possessive('Арна', Q::PERSON_1SG, Q::DATIVE));
        self::assertSame('Нұрланының', $inflector->possessive('Нұрлан', Q::PERSON_3, Q::GENITIVE));
        self::assertSame('Арналарыңыз', $inflector->possessive('Арна', Q::PERSON_2PL_FORMAL));
        self::assertSame('Арналарым', $inflector->possessive('Арна', Q::PERSON_1SG, plural: true));
        self::assertSame('кітабы', $inflector->possessive('кітап'));
        self::assertSame('Нұрланның әкесі', $inflector->genitivePhrase('Нұрлан', 'әке'));
        self::assertSame('менің кітабым', $inflector->genitivePhrase('мен', 'кітап'));
        self::assertSame('студентпін', $inflector->predicate('студент', Q::PERSON_1SG));
    }

    public function testDeclension(): void
    {
        $table = (new Q())->declension('Нұрлан');
        self::assertSame(Q::CASES, array_keys($table));
        self::assertSame(['Нұрланның', 'Нұрландардың'], $table[Q::GENITIVE]);
    }

    public static function strictCalls(): iterable
    {
        yield 'case' => [fn (Q $i) => $i->inflect('Нұрлан', 'dativ')];
        yield 'pronoun case' => [fn (Q $i) => $i->inflect('мен', 'dativ')];
        yield 'possessive person' => [fn (Q $i) => $i->possessive('Арна', '4')];
        yield 'possessive 3 case' => [fn (Q $i) => $i->possessive('Арна', '3', 'dativ')];
        yield 'possessive 1sg case' => [fn (Q $i) => $i->possessive('Арна', '1sg', 'dativ')];
        yield 'predicate person' => [fn (Q $i) => $i->predicate('студент', '4')];
    }

    #[DataProvider('strictCalls')]
    public function testStrictModeThrows(callable $call): void
    {
        $this->expectException(InvalidArgumentException::class);
        $call(new Q(strict: true));
    }
}
