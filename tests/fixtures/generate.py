"""Эталонные формы из Python-версии qazaq_inflector: PHP-порт обязан совпадать с ними байт в байт.

pip install qazaq_inflector && python tests/fixtures/generate.py
"""
import json
import pathlib

from qazaq_inflector import QazaqNameInflector

NAMES = [
    'Нұрлан', 'Арна', 'Сәуле', 'Айгүл', 'Абай', 'Әли', 'Ләззат', 'Аян', 'Ерлан', 'Мәдина', 'Ержан', 'Мейрам',
    'Айбаз', 'Сұлтанбек', 'Бақыт', 'Дәулет', 'Асқар', 'Гүлнар', 'Жанар', 'Мұрат', 'Ботагөз', 'Динара', 'Ғалым',
    'Төлеген', 'Әсел', 'Қуаныш', 'Ақбота', 'Еркебұлан', 'Шолпан', 'Жүсіп', 'Мұхаммед', 'Ахмед', 'Тимур', 'Олжас',
    'Аружан', 'Нұрсұлтан', 'Серік', 'Ілияс', 'Өмірзақ', 'Үміт', 'Айдос', 'Ринат', 'Азамат', 'Іңкәр', 'Ұлжан',
    'Құнанбаев', 'Назарбаева', 'Ахметов', 'Ахметова', 'Әлиев', 'Әлиева', 'Сейітова', 'Байтұрсынұлы', 'Иванов',
    'кітап', 'ақ', 'әке', 'бала', 'үй', 'дос', 'студент', 'қазақ', 'мұғалім', 'оқушы', 'дәрігер', 'қыз', 'жас',
    'Абай Құнанбаев', 'Ахмет Байтұрсынұлы', 'Гүлнар-Баян', 'НҰРЛАН', 'Әлия Нұрланқызы',
]
PRONOUNS = ['мен', 'Мен', 'сен', 'сіз', 'Сіз', 'ол', 'біз', 'сендер', 'сіздер', 'олар']
PERSONS = ['1sg', '2sg', '2sg_formal', '1pl', '2pl', '2pl_formal', '3']
OWNERS = ['Нұрлан', 'Арна', 'Абай Құнанбаев', 'мен', 'Сіз', 'сендер', 'олар']
THINGS = ['әке', 'кітап', 'бала', 'үй', 'дос']

i = QazaqNameInflector()
data = {'inflect': [], 'pluralize': [], 'possessive': [], 'genitive_phrase': [], 'predicate': []}
for word in NAMES + PRONOUNS:
    for case in QazaqNameInflector.CASES:
        data['inflect'].append([word, case, i.inflect(word, case)])
for word in NAMES:
    data['pluralize'].append([word, i.pluralize(word)])
    for person in PERSONS:
        data['predicate'].append([word, person, i.predicate(word, person)])
        for case in QazaqNameInflector.CASES:
            for plural in (False, True):
                data['possessive'].append([word, person, case, plural, i.possessive(word, person, case, plural)])
for owner in OWNERS:
    for thing in THINGS:
        for case in QazaqNameInflector.CASES:
            for plural in (False, True):
                data['genitive_phrase'].append([owner, thing, case, plural, i.genitive_phrase(owner, thing, case, plural)])

out = pathlib.Path(__file__).with_name('python_reference.json')
out.write_text(json.dumps(data, ensure_ascii=False, indent=0), encoding='utf-8')
print({k: len(v) for k, v in data.items()})
