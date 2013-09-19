<?
  /* 
      .:: Yandex OAuth ::. 

        Version : 0.2b 
        Author : [di7spider] Gurov Dmitry ( gurov.dimon@gmail.com )      
  */

  class YOAuth
    {
        function __construct(){}

        static public function _()
         {  
            return new self;
         }

        // Параметры
        static protected $_PARAMS = Array(
            
            // Авторизация 
            'AUTH' => Array(

                // Пользователь
                'USER' => Array(
                    'LOGIN' => '',
                    'PASSWORD' => ''
                 ),

                // Приложение
                'APP' => Array(
                    'ID' => '', 
                    'PASSWORD' => '' 
                )
            ),

            // API URL
            'API_URL' => Array(
                'passport' => 'passport.yandex.ru/passport', //  Get Auth Cookie
                'authorize' => 'oauth.yandex.ru/authorize', // Get Auth User Code
                'token' => 'oauth.yandex.ru/token' // Get OAuth token
            ),

            // Session Name
            'SESSIONS' => Array(
                'COOKIE' => '_AUTH_COOKIE_',
                'CODE' => '_AUTH_CODE_',
                'TOKEN' => '_AUTH_TOKEN_'
            )

        ); 

        /*
            Info : Получает Cookie пользователя.
            Return :  (string) Cookie
        */
        static protected function _getCookie()
         {
            $arParams = self::getParams();

            if(is_array($arParams)){

                $sessCookie = $arParams['SESSIONS']['COOKIE'];

                // Если уже есть полученные Cookie
                if(!empty($_SESSION['_YD_'][$sessCookie]))
                    return $_SESSION['_YD_'][$sessCookie];

                $arResult = self::_httpQuery(
                    $arParams['API_URL']['passport'], 
                    Array(), 
                    false, 
                    Array('browser')
                );

                $data = $arResult['CONTENT'];

                if(!empty($data)){

                     $objInputs = self::_getXPathXML($data)-> query("//input");

                     if(!is_null($objInputs)){

                        foreach($objInputs as $objInput){

                            $name = htmlspecialchars($objInput-> getAttribute('name'));
                            $value = htmlspecialchars($objInput-> getAttribute('value'));

                            if(!empty($name))
                                $arDataSend[$name] = $value;
                        }
                    }
                }

                if(is_array($arDataSend)){

                    $arDataSend = array_merge($arDataSend, Array(
                        'login' => htmlspecialchars($arParams['AUTH']['USER']['LOGIN']),
                        'passwd' => htmlspecialchars($arParams['AUTH']['USER']['PASSWORD'])
                    ));
           
                    $arResult = self::_httpQuery(
                        $arParams['API_URL']['passport'], 
                        $arDataSend,
                        'POST',
                        false,
                        Array(
                            'header',
                            'browser'
                        )
                    );

                    $data = $arResult['CONTENT'];
 
                    if(!empty($data)){

                        preg_match_all("/Set-Cookie: (.*?=.*?);/i", $data, $arCookies);

                        $arCookies = $arCookies[1];

                        if(is_array($arCookies) && count($arCookies) > 0){

                            return ( $_SESSION['_YD_'][$sessCookie] = htmlspecialchars(implode("; ", $arCookies) ));
                        
                        }else
                            throw new Exception('Не удалось получить Cookie пользователя (:');
                    }

                }else
                    throw new Exception('Не удалось получить данные со страницы авторизации (:');
             }
         }

        /*
            Info : Получает Code пользователя.
            Return :  (string) Code
        */
        static protected function _getCode()
         {
            $arParams = self::getParams();

            if(is_array($arParams)){

                $sessCode = $arParams['SESSIONS']['CODE'];

                // Если уже есть полученные Cookie
                if(!empty($_SESSION['_YD_'][$sessCode]))
                    return $_SESSION['_YD_'][$sessCode]; 

                $arResult = self::_httpQuery(
                    $arParams['API_URL']['authorize'], 
                    Array(
                        'response_type' => 'code',
                        'client_id' => $arParams['AUTH']['APP']['ID']
                    ),
                    'GET',
                    false,
                    Array(
                      'cookie'
                    )
                );

                $data = $arResult['CONTENT'];

                if(!empty($data)){

                    $code = self::_getXPathXML($data)-> query("//*[@class='confirm-code']/*/code")-> item(0)-> nodeValue;

                    if(!empty($code)){

                        $code = intVal($code);

                        if($code > 0){

                            return ( $_SESSION['_YD_'][$sessCode] = $code );
                        
                        }else
                            throw new Exception('Не удалось получить Code авторизации пользователя (:');
                    }
                }
             }
         }

        /*
            Info : Получает OAuth Token пользователя.
            Return :  (string) Token
        */
        static protected function _getToken()
         {
            $arParams = self::getParams();

            if(is_array($arParams)){

                $sessToken = $arParams['SESSIONS']['TOKEN'];

                // Если уже есть полученные Cookie
                if(!empty($_SESSION['_YD_'][$sessToken]))
                    return $_SESSION['_YD_'][$sessToken]; 

                $arResult = self::_httpQuery(
                    $arParams['API_URL']['token'], 
                    Array(
                        'grant_type' => 'authorization_code',
                        'code' => self::_getCode(),
                        'client_id' => $arParams['AUTH']['APP']['ID'],
                        'client_secret' => $arParams['AUTH']['APP']['PASSWORD']
                    ),
                    'POST'
                );

                $data = $arResult['CONTENT'];

                if(!empty($data)){

                    $data = json_decode($data, true);

                    if(array_key_exists('error', $data)){

                         throw new Exception($data['error_description']);
                    
                    }else if(array_key_exists('access_token', $data)){

                        return ( $_SESSION['_YD_'][$sessToken] = $data['access_token'] );
                    }
                }
             }
         }

         /*
            Info :  Возвращает объект XML DOM XPath
            Params : (string) @param1 

            Return :  (object) DomDocument
        */
        static protected function _getXPathXML($xmlData = '')
         {
            if(!empty($xmlData)){

                $XML = new DomDocument;

                $XML-> loadHTML($xmlData);

                return new DomXPath($XML);
            }
         }
        
        /*
            Info :  Сливаем два массива
            Params : (array) @param1 
                     (array) @param2  

            Return :  (array) @param1 + @param2
        */
        static protected function _getArrMerge($arr1 = false, $arr2 = false)
        {   
            if(is_array($arr1) && is_array($arr2)){

                foreach($arr2 as $k => $v){
                    $arr1[$k] = $v;
                }

                return $arr1;
            }
        }

         /*
            Info :   Функция осуществляет CURL запросы
            Params : (string) @param1 - URL на который нужно отправить запрос
                     (array)  @param2 - GET / POST массив параметров которые необходимо отправить
                     (string) @param3 - GET / POST метод используемый при отправки 
                     (array)  @param4 - массив дополнительных параметров CURL
                     (array)  @param5 - кастомные CURL параметры

            Return :  (array) - Результат запроса
        */
        static protected function _httpQuery($url = '', $arData = false, $method = 'GET', $arCurl = false, $arParams = false)
          {
              if(!empty($url)){

                 $arData = is_array($arData) ? $arData : Array();

                 $arCurlParams = Array();

                 $url = 'https://'.$url;

                 switch( strtoupper($method) == 'GET' ? 'GET' : 'POST' ){
                    
                    case 'POST':
                         
                             $arCurlParams = Array(
                                CURLOPT_POST => 1,
                                CURLOPT_POSTFIELDS => $arData
                             );
                         
                        break;

                    case 'GET':
                    default:
                        
                            $url .= '?'.http_build_query($arData);

                        break;
                 }
        
                 // Default CURL Params
                 $arCurlParams = self::_getArrMerge(
                    Array(
                        CURLOPT_URL => $url,
                        CURLOPT_HEADER => 0,
                        CURLOPT_NOBODY => 0,
                        CURLOPT_RETURNTRANSFER => 1,
                        CURLOPT_FOLLOWLOCATION => 1, 
                        CURLOPT_SSL_VERIFYPEER => 0,
                        CURLOPT_MAXREDIRS => 3,
                        CURLOPT_CONNECTTIMEOUT => 10,
                        CURLOPT_TIMEOUT => 20
                    ), 
                    $arCurlParams
                 );

                 // Параметры CURL
                 if(is_array($arCurl)){

                    $arCurlParams = self::_getArrMerge($arCurlParams, $arCurl);
                 }

                 // Кастомыне параметры CURL
                 if(is_array($arParams)){

                    foreach($arParams as $action){
                        
                        switch(strtolower($action)){
                            
                            // Если необходимо получить Header без содержимого страницы
                            case 'header':
                                
                                    $arCurlPRM = Array(
                                        CURLOPT_NOBODY => 1,   // не нужно содержание страницы
                                        CURLOPT_HEADER => 1,  // необходимо получить HTTP заголовки
                                    );

                                break;

                            // Если необходим HTTP_USER_AGENT
                            case 'browser':

                                    $userAgent = htmlspecialchars(substr(trim($_SERVER['HTTP_USER_AGENT']), 0, 150));
                                    if(empty($userAgent))
                                        $userAgent = 'Mozilla/5.0 (Windows; U; Windows NT 6.1; en-US; rv:1.9.1.6) Gecko/20091201 Firefox/3.5.6 (.NET CLR 3.5.30729)'; 

                                    $acceptLanguage = htmlspecialchars(substr(trim($_SERVER['HTTP_ACCEPT_LANGUAGE']), 0, 150)); 
                                    if(empty($acceptLanguage))
                                        $acceptLanguage = 'en-us,en;q=0.5';   

                                    $arCurlPRM = Array(
                                        CURLOPT_HTTPHEADER => Array(
                                             "User-Agent: ".$userAgent,
                                             "Accept-Language: ".$acceptLanguage
                                         )
                                    );

                                break;

                            // Если необходимы Cookie пользователя
                            case 'cookie' : 

                                    $cookie = self::_getCookie();

                                    if(!empty($cookie)){
                                        
                                        $arCurlPRM = Array(
                                            CURLOPT_COOKIE => $cookie
                                        );

                                    }

                                break;  
                        }

                        // Если уже есть "CURLOPT_HTTPHEADER"
                        if(is_array($arCurlPRM)){

                            if(array_key_exists(CURLOPT_HTTPHEADER, $arCurlPRM) 
                                && array_key_exists(CURLOPT_HTTPHEADER, $arCurlParams)){

                                $arCurlPRM[CURLOPT_HTTPHEADER] = array_merge(
                                    $arCurlParams[CURLOPT_HTTPHEADER], 
                                    $arCurlPRM[CURLOPT_HTTPHEADER]
                                );
                            }

                            $arCurlParams = self::_getArrMerge($arCurlParams, $arCurlPRM);
                        }

                        unset($arCurlPRM);
                    }
                 }

                 $curl = curl_init();

                 curl_setopt_array($curl, $arCurlParams);
                 
                 $output = curl_exec($curl); // Получаем HTML в качестве результата
                    
                 $status = curl_getinfo($curl); // Инфо

                 curl_close($curl); //  Закрываем соединение

                 return Array(
                    'CONTENT' => $output,
                    'STATUS' => $status
                 );
              }
        }

        /*
            Info : Получает параметы класса.
            Return :  (array) - $_PARAMS
        */
        static public function getParams()
         {
            return self::$_PARAMS;
         }

        /*
            Info :   Функция устанавливает параметры класса
            Params : (array)   @param1 - Параметры
                     (string)  @param2 - Тип параметров
        */
        static public function setParams($params = false, $type = false)
         {
            if(is_array($params)){

                if(!$type)
                   $type = 'AUTH';
                
                $type = strtoupper($type);

                $arParams = self::getParams();

                if(array_key_exists($type, $arParams)){

                    self::$_PARAMS[$type] = $params;
                }
            }    
         }

        /*
            Info :    Получает Yandex OAuth Token (если Token, уже ранее был получен, то возвращается он)
            Return :  (string) Token
        */
        static public function getToken()
         {   
             return self::_getToken();
         }

        /*
            Info :    Получает всегда новый Yandex OAuth Token
            Return :  (string) Token
        */
        static public function getNewToken()
         {
            unset($_SESSION['_YD_']);

            return self::getToken();
         }  
    }
?>