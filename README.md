Yandex OAuth Token
========

http://api.yandex.ru/oauth/doc/dg/reference/obtain-access-token.xml

-------------------------
Класс YOAuth, осуществляет процедуру получения Yandex OAuth Token, необходимого для взаимодействия с Yandex API

-------------------------
```php
try{
    
    // Установить параметры авторизации
    YOAuth::setParams(Array(

        // Пользователь
        'USER' => Array(
            'LOGIN' => 'логин пользователя',
            'PASSWORD' => 'пароль пользователя'
         ),

        // Приложение
        'APP' => Array(
            'ID' => 'ID приложения', 
            'PASSWORD' => 'Пароль приложения' 
        )
    ));

    // Получить токен (c сохранением в Session)
    $token = YOAuth::getToken();

    // Получить новый токен (без сохранения в Session)
    // $token = YOAuth::getNewToken();

    // Yandex OAuth Token
    echo $token;

}catch(Exception $e){
    
    // Show Error   
    echo $e-> getMessage();
}
```
