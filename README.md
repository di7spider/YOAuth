Yandex OAuth Token
========

http://api.yandex.ru/oauth/doc/dg/reference/obtain-access-token.xml

-------------------------
Класс YOAuth предназначен для получения Yandex OAuth Token, используемого для взаимодействия с Yandex API

-------------------------
```
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
    $token = YOAuth::getNewToken();

    // Получить токен (без сохранения в Session)
    // $token = YOAuth::getNewToken();

    // Yandex OAuth Token
    echo $token;

}catch(Exception $e){
    
    // Show Error   
    echo $e-> getMessage();
}
```
