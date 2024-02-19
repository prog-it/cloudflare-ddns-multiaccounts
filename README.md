# Cloudflare DDNS-client v1.1.10

DDNS-клиент для сервиса [Cloudflare](https://www.cloudflare.com/).


## Основные возможности
- Работа с несколькими аккаунтами Cloudflare
- Работа с несколькими доменами. Например: domain1.com, domain2.com
- Обновление нескольких DNS записей для домена
- Настройка параметров для каждой DNS-записи:
	- Time to Live (TTL)
	- Режим проксирования запросов
- Поддержка IPv4 и IPv6
- Выбор режима обновления:
	- Только IPv4
	- Только IPv6
	- IPv4 и IPv6 одновременно
- Выбор режима получения текущего IP: 
	- HTTP-запрос
	- DNS lookup (dig)
- При отсутствии DNS записи, она будет создана
- Выбор режима логирования событий: 
	- Полный
	- Краткий
	- Отключен
- Автоочистка лог-файла. При превышении заданного размера, файл будет очищен
- Возможность запуска обновления по CRON или GET-запросом (Например с помощью утилиты WGET)
- Возможность добавить несколько URL-сервисов получения текущего IP адреса для HTTP-метода. URL сервиса будет выбран случайным образом
- Возможность указать количество попыток получения текущего IP адреса для HTTP-метода. Если IP получить не удалось, будет выбран другой URL-сервиса


## Прежде, чем начать использовать
Прежде, чем начать использовать, убедитесь, что у вас есть следующая информация или вы выполнили следующие шаги:

 1. *Аккаунт Cloudflare:*
 
	a. У вас есть зарегистрированный аккаунт Cloudflare (или [создайте новый аккаунт Cloudflare, если его еще нет](https://dash.cloudflare.com/sign-up))
	
	b. Есть [API токен](https://dash.cloudflare.com/profile/api-tokens) - нет необходимости использовать глобальный ключ API (legacy)! (Подробнее: [Ключи API](https://support.cloudflare.com/hc/en-us/articles/200167836-Managing-API-Tokens-and-Keys))

	![image](https://github.com/prog-it/cloudflare-ddns-multiaccounts/blob/master/docs/example1.jpg)
	
	![image](https://github.com/prog-it/cloudflare-ddns-multiaccounts/blob/master/docs/example2.jpg)


	c. Создайте API токен со следующими (3) разрешениями:

	**Zone** > **Zone.Settings** > **Read**  
	**Zone** > **Zone** > **Read**  
	**Zone** > **DNS** > **Edit**  

	Ресурсы зоны (Zone Resources) должны быть:

	**Include** > **All zones from an account** > `<domain>`
	
	Или выбрать нужные домены, например:
	
	**Include** > **Specific zone** > `domain1.com`


## Как использовать
Основные настройки скрипта находятся в файле `config/config.php`, этот файл необходимо создать. Для этого нужно открыть файл `config/config.php.sample`, настроить в нем параметры и сохранить как `config/config.php`.


В папке `config/entries` должны располагаться файлы с параметрами доменов для обновления их DNS-записей. Таких файлов может быть несколько, можно задавать произвольное имя, главное, чтобы расширение у файлов было `.php`. Например: domain1.com.php

В папке `config/entries` находится файл `entry.php.sample`, как пример для создания такого файла с параметрами. Его необходимо открыть, настроить в нем параметры и сохранить с произвольным именем, например: `config/entries/domain1.com.php`.

При вызове скрипта `update.php` можно добавить параметр entry. Этот параметр позволяет обновить только одну запись из папки `config/entries`, а не все сразу. Если вызвать скрипт без этого параметра, будут обновлены все записи, которые находятся в папке `config/entries`

Для автоматического запуска обновления IP-адреса можно создать CRON-задачу или вызвать скрипт `update.php` GET-запросом с параметром token (и entry). Вместо startup_token указать токен запуска из основного конфиг-файла (находится в самом низу)

*Примеры вызова для обновления всех записей*

CRON-задача

``*/5 * * * * php /path/to/cloudflare-ddns-multiaccounts/update.php --token="startup_token"``

Вызов GET-запросом

``http://example.com/cloudflare-ddns-multiaccounts/update.php?token=startup_token``

*Примеры вызова для обновления одной записи "example.com"*. Для этого в папке `config/entries` должен существовать файл `example.com.php`

CRON-задача

``*/5 * * * * php /path/to/cloudflare-ddns-multiaccounts/update.php --token="startup_token" --entry="example.com"``

Вызов GET-запросом

``http://example.com/cloudflare-ddns-multiaccounts/update.php?token=startup_token&entry=example.com``

Также в папке `server` находится скрипт для получения текущего IP-адреса с помощью HTTP-запроса. Этот скрипт можно разместить на своем сервере и добавить URL этого скрипта в основной конфиг-файл. 
Например: ``http://example.com/ip.php``. Если вызвать скрипт с параметром ``?raw``, то он вернет только IP-адрес, например: ``http://example.com/ip.php?raw``.


Кратко:
1. Скачать скрипт как ZIP-архив или выполнить: ``git clone https://github.com/prog-it/cloudflare-ddns-multiaccounts.git``
2. Перейти в папку со скриптом: ``cd cloudflare-ddns-multiaccounts``
3. Создать основной конфиг-файл "config/config.php: ``cp config/config.php.sample config/config.php``. Настройки по умолчанию оптимальны, но можно изменить на свои. В файле есть подробные комментарии
4. Создать первый файл с параметрами для обновления DNS записей домена: "config/entries/entry1.php": ``cp config/entries/entry.php.sample config/entries/entry1.php``. Настроить параметры в этом файле, в нем есть подробные комментарии. Таких файлов можно создавать несколько (с разными именами), в каждом свои параметры для обновления DNS записей
5. Создать CRON задачу обновления IP. Вместо startup_token указать токен запуска CRON из конфиг-файла (находится в самом низу)

``*/5 * * * * php /path/to/cloudflare-ddns-multiaccounts/update.php --token="startup_token"``


## Некоторые особенности
- Если для DNS записи (Например: my.computer.example.com) используется несколько IP адресов, то будет обновлен IP только у первой по счету


## Системные требования
- PHP 5.4 и выше
- PHP библиотеки: cURL, php-mod-tokenizer (PHP7 - php7-mod-tokenizer, PHP5 - php5-mod-tokenizer)
- Утилита DNS lookup (dig) из пакета "bind-dig", если выбран способ получения IP - "dig"


Если нашлись какие-либо баги или недоработки, то оставляйте свои заявки в разделе [**Issues**](https://github.com/prog-it/cloudflare-ddns-multiaccounts/issues)

Историю изменений можно посмотреть [здесь](https://github.com/prog-it/cloudflare-ddns-multiaccounts/releases). Текущая версия DDNS-клиента находится в файле: ``README.md``

