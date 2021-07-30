## Форма авторизации

На форме расположены поля ввода: логин, пароль
При вводе логина и пароля производится либо вход либо регистрация и
последующий вход.
После регистрации должно выводиться однократно сообщение с поздравлением с
успешной регистрацией.
Пароли в базе должны храниться с уникальной «солью».
Авторизация пользователя не должна сбрасываться при закрытии
браузера/вкладки и должна действовать 1 час с момента последнего действия и
проверяться со стороны сервера.

## Список картинок

После авторизации становится доступна страница со списком картинок. На ней
отображаются картинки (размер 100px по ширине, высота auto с сохранением
пропорций, картинка должна сжиматься средствами php) и количество
комментариев к ним.
Комментарии должны считаться с помощью триггера mysql.
Должна быть кнопка «подробнее» под каждой записью.

## Страница подробнее

На странице отображается картинка в полном размере, автор, время добавления,
комментарии, форма добавления комментария.
Можно удалить свой комментарий и отредактировать. У отредактированного
комментария должна быть надпись [Edited] и должна быть возможность
просмотреть историю комментариев (редактировать возможно неоднократно).
При добавлении комментария должна быть проверка на запрещённые слова: «лес,
поляна, озеро», она не должна срабатывать на слова которые содержат в себе эти
слова, к примеру она не должна сработать на слово «лесной», проверка должна
быть регистр независимой.
Редактирование доступно если с момента добавления комментария прошло менее
5 минут.
Также надо вести счетчик просмотра картинок.

## Добавление картинки

Картинку может добавить авторизированный пользователь.
Размер картинки не должен быть более 1500px по самой большой стороне.
Вес картинки должен быть не менее 250кб и не более 5мб.
Пользователь может загружать 1 картинку не чаще раза в 3 минуты, с
соответствующим предупреждением.
Нельзя загрузить 2 и более одинаковых картинки, если время загрузки
предыдущей картинки менее 15 минут.
**Неавторизированный доступ к картинкам по прямой ссылке должен быть**
**запрещен, картинку нельзя хранить в базе.**

## Общие требования

Работа должна быть выполнена на чистом php 7+ (без использования
фреймворков), база mysql, допустимо использовать Memcached в дополнение к
mysql.
Работа должна работать без ошибок с уровнем error_reporting E_ALL, не должно
быть супрессоров ошибок а также оператора управления ошибок.
Все проверки должны быть выполнены на стороне сервера (должно корректно
работать с отключённым js).
Можно использовать сторонние библиотеки.
Тестовое задание ожидается в zip архиве в папке public_html все файлы проекта, в
папке mysql дамп базы.
Проект будет запускаться на версии php 7.4.12 с стандартным набором модулей, и
версией mysql 5.7.32 входящих в пакет MAMP.