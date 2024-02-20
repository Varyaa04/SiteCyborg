<?php
/**
 * Основные параметры WordPress.
 *
 * Скрипт для создания wp-config.php использует этот файл в процессе установки.
 * Необязательно использовать веб-интерфейс, можно скопировать файл в "wp-config.php"
 * и заполнить значения вручную.
 *
 * Этот файл содержит следующие параметры:
 *
 * * Настройки базы данных
 * * Секретные ключи
 * * Префикс таблиц базы данных
 * * ABSPATH
 *
 * @link https://ru.wordpress.org/support/article/editing-wp-config-php/
 *
 * @package WordPress
 */

// ** Параметры базы данных: Эту информацию можно получить у вашего хостинг-провайдера ** //
/** Имя базы данных для WordPress */
define( 'DB_NAME', 'cyborgBD' );

/** Имя пользователя базы данных */
define( 'DB_USER', 'root' );

/** Пароль к базе данных */
define( 'DB_PASSWORD', '' );

/** Имя сервера базы данных */
define( 'DB_HOST', 'localhost' );

/** Кодировка базы данных для создания таблиц. */
define( 'DB_CHARSET', 'utf8mb4' );

/** Схема сопоставления. Не меняйте, если не уверены. */
define( 'DB_COLLATE', '' );

/**#@+
 * Уникальные ключи и соли для аутентификации.
 *
 * Смените значение каждой константы на уникальную фразу. Можно сгенерировать их с помощью
 * {@link https://api.wordpress.org/secret-key/1.1/salt/ сервиса ключей на WordPress.org}.
 *
 * Можно изменить их, чтобы сделать существующие файлы cookies недействительными.
 * Пользователям потребуется авторизоваться снова.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',         'B,LXEw,@W&LQ+l)h4Lo1A^I/H>Iydfi7RtKg1AD[Xma9$SW_@q|:s-w}(1&3zIx)' );
define( 'SECURE_AUTH_KEY',  ')S)u11vi7TG0r0&Pk0d,khg/]=3>g2ac=+f=int0-oJ9Kb0f^YcdZkg`wG$!t+xt' );
define( 'LOGGED_IN_KEY',    'u|bPF*&owjw;T=410ec)vnhpRhl0nBVy9y@dz9Oe^bg4P-9L`|-$}V+@q}((D2_0' );
define( 'NONCE_KEY',        'p97dxH2(~@}P0cjoX5syN<#tt!S.T05U!<PsFNrzwCG#T3O7}2RyH+<jxG+=zPE&' );
define( 'AUTH_SALT',        'vlA#1>5Jo7Fr^0qW2EP6@4OAY3FteO0Hr3Vmowu3.7u.#i*0#RMr~r&@M-&JZL3)' );
define( 'SECURE_AUTH_SALT', 'iK.@+;<mme=~bd^/06CkxzuGM*rn{Di~KE),SYE}(Y1c^+J8hTn-^d[w({Pt U-a' );
define( 'LOGGED_IN_SALT',   'I@3Am#B5n k$1Cc*P;0o^QT&x$}dsJZ_{/[AivZg0C{T:t)KVbV>VC%A$~k-u^JI' );
define( 'NONCE_SALT',       '{=Ge&n0:|9MU&Pcs~6eRA!z5U*7yBR^M}[^Djf-YInrc?n^?~]ipaPT`L#PqHH:|' );

/**#@-*/

/**
 * Префикс таблиц в базе данных WordPress.
 *
 * Можно установить несколько сайтов в одну базу данных, если использовать
 * разные префиксы. Пожалуйста, указывайте только цифры, буквы и знак подчеркивания.
 */
$table_prefix = 'wp_';

/**
 * Для разработчиков: Режим отладки WordPress.
 *
 * Измените это значение на true, чтобы включить отображение уведомлений при разработке.
 * Разработчикам плагинов и тем настоятельно рекомендуется использовать WP_DEBUG
 * в своём рабочем окружении.
 *
 * Информацию о других отладочных константах можно найти в документации.
 *
 * @link https://ru.wordpress.org/support/article/debugging-in-wordpress/
 */
define( 'WP_DEBUG', false );

/* Произвольные значения добавляйте между этой строкой и надписью "дальше не редактируем". */



/* Это всё, дальше не редактируем. Успехов! */

/** Абсолютный путь к директории WordPress. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Инициализирует переменные WordPress и подключает файлы. */
require_once ABSPATH . 'wp-settings.php';
