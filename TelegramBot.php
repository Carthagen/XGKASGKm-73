<?php
global $data_photo, $data_message, $callback_query_id, $callback_query_data, $forward_from_message_id, $reply_to_message_forward_from_id;
$data_photo = [];
$data_message = ''; // $data_message - Это сырое сообщение без изменений и редакции
$callback_query_id = 0;
$callback_query_data = [];
$forward_from_message_id = '';    // Проверка. Сообщение было переслано или нет?
$reply_to_message_forward_from_id = ''; // Проверка. Это ответ на чьето сообщение?

// Это бот Учет на рыбной ферме
class TelegramBot extends Model
{
    public $token = '******';   // По умолчанию, это токен
    
    public $log_file = WB_PATH.'/telegram_akvaferma_bot.txt';   // По умолчанию, это лог файл куда пишем все данные о диалогах @akvaferma_bot info
    
    
    /*
    Field 	Type 	Description
    url 	String 	Webhook URL, may be empty if webhook is not set up
    has_custom_certificate 	Boolean 	True, if a custom certificate was provided for webhook certificate checks
    pending_update_count 	Integer 	Number of updates awaiting delivery
    last_error_date 	Integer 	Optional. Unix time for the most recent error that happened when trying to deliver an update via webhook
    last_error_message 	String 	Optional. Error message in human-readable format for the most recent error that happened when trying to deliver an update via webhook
    max_connections 	Integer 	Optional. Maximum allowed number of simultaneous HTTPS connections to the webhook for update delivery
    allowed_updates 	Array of String 	Optional. A list of update types the bot is subscribed to. Defaults to all update types
    */
	
	//Функция отправки сообщений. Кроме sendPhoto
    public function sendRequest($method, $params = [], $first_name='') {
        $url  = 'https://api.telegram.org/bot';
        $url .= $this->token;
        $url .= '/';
        $url .= $method;
        
        //if(!empty($params)) {
        //    $url .= '?' . http_build_query($params);
        //} 
        //echo $url;
        //$params['chat_id']       = 753803761; //Проверка, как выглядит ошибка
        
        //file_put_contents(WB_PATH.'/application/lib/telegram/log.txt', date("Y-m-d H:i:s").' $url: '.$params."\n", FILE_APPEND);
        
        
        ((is_array($params)) || (is_object($params))) ? file_put_contents($this->log_file, date("Y-m-d H:i:s").' Params: '.print_r($params, TRUE), FILE_APPEND) : file_put_contents($this->log_file, 'Params: '.$params."\n", FILE_APPEND);
        
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_POST => 1,
            CURLOPT_HEADER => 0,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_URL => $url,
            CURLOPT_POSTFIELDS => json_encode($params),
            CURLOPT_HTTPHEADER => array_merge(array("Content-Type: application/json"))
        ]);
        
        $result = curl_exec($curl);
        curl_close($curl);
        
        file_put_contents($this->log_file, 'Result: '.$result."\n\n", FILE_APPEND);
        
        // Ловим ошибки и если они есть, то пишем в базу данных код ошибки, описание и имя пользователя
        $json = json_decode($result, true);
        if(!$json['ok']) {
            $arrData['error_code']  = $json['error_code'];
            $arrData['description'] = $json['description'];
            $arrData['first_name']  = $first_name;
            $arrData['chat_id']     = $params['chat_id'];
            
            saveRed('telegramerror', $arrData);
        }
        //file_put_contents($this->log_file, 'Json: '.print_r($json, TRUE)."\n\n", FILE_APPEND);
        
        return (json_decode($result, 1) ? json_decode($result,1) : $result);
        
        //exit(0);
        //return json_decode(
        //    file_get_contents($url),
       //     JSON_OBJECT_AS_ARRAY
        //);
    }
    
    
	/*Функция отправки фото. sendPhoto
    *	
    * @param int     $chat_id           - номер чата или канала
    * @param text    $files             - путь к файлу фото
    * @param array   $caption           - заглавие картинки
    * @param array   $caption_entities  - Оформление заголовка к картинке
    
    
    * @return array      $result
    * 
    */  
    public function sendPhoto( $chat_id, $files, $caption, $caption_entities= array(), $reply_markup='') {
        $url  = 'https://api.telegram.org/bot';
        $url .= $this->token;
        $url .= '/';
        $url .= "sendPhoto?chat_id=" . $chat_id;
        
        
        file_put_contents($this->log_file, date("Y-m-d H:i:s").' Caption_entities: '.print_r($caption_entities, TRUE), FILE_APPEND);
        
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_POST => 1,
            CURLOPT_HEADER => 0,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_URL => $url,
            CURLOPT_POSTFIELDS => array( "photo" => $files, "caption" => $caption, "caption_entities" => json_encode($caption_entities), "reply_markup" => $reply_markup),
            CURLOPT_HTTPHEADER => array_merge(array("Content-Type:multipart/form-data"))
        ]);
        
        
        $result = curl_exec($curl);
        curl_close($curl);
        
        file_put_contents($this->log_file, 'Result: '.$result."\n\n", FILE_APPEND);
        
        return (json_decode($result, 1) ? json_decode($result,1) : $result);
        
    }
	
	public function get_data()
	{	
		global $data_photo, $data_message, $callback_query_id, $callback_query_data, $forward_from_message_id, $reply_to_message_forward_from_id;  
		
		//Рсшифровываем и возвращаем
		$arrData = json_decode(file_get_contents('php://input'), TRUE);
		// Прочесть запрос от сервера и записать в файл
		file_put_contents($this->log_file, date("Y-m-d H:i:s").' $arrData: '.print_r($arrData,1), FILE_APPEND);
		
		//Обрабатываем ручной ввод или нажатие на кнопку
		// если это просто объект message
		
		if(!$arrData) return false;
        if (array_key_exists('message', $arrData)) {
            if (array_key_exists("text", $arrData['message'])) {
                
                // From User
                $from_id    = $arrData["message"]["from"]["id"];
                $is_bot     = $arrData["message"]["from"]["is_bot"];
                $language_code = empty($arrData["message"]["from"]["language_code"])? '': $arrData["message"]["from"]["language_code"];
                $username   = empty($arrData["message"]["from"]["username"])? '': $arrData["message"]["from"]["username"];
                $first_name = empty($arrData["message"]["from"]["first_name"])? '': $arrData["message"]["from"]["first_name"];
                $last_name  = empty($arrData["message"]["from"]["last_name"])? '': $arrData["message"]["from"]["last_name"];
                
                // текстовое значение 
                $message    = empty($arrData['message']['text'])? '': $arrData['message']['text'];
                
                // Chat получаем id чата
                $chat_id    = $arrData['message']['chat']['id'];
                
                // message получаем id
                $message_id    = $arrData['message']['message_id'];
                
                // Проверяем. Это сообщение не было переслано
                if (array_key_exists("forward_from_message_id", $arrData['message'])) $forward_from_message_id = $arrData['message']['forward_from_message_id'];
                
                // Проверяем. Это сообщение было ответ на чьето сообщение?
                if (array_key_exists("reply_to_message", $arrData['message']) AND array_key_exists("forward_from", $arrData['message']['reply_to_message']) ) $reply_to_message_forward_from_id = $arrData['message']['reply_to_message']['forward_from']['id'];
                
            } 
            else if (array_key_exists("new_chat_participant", $arrData['message'])) {
                
            // Проверка на служебное сообщение о вступлении в группу
                // текстовое значение 
                $message = 'new_chat_participant';
                
                // Chat получаем id чата
                $chat_id = $arrData['message']['chat']['id'];
                
                // message получаем id
                $message_id    = $arrData['message']['message_id'];
                
                // Проверяем. Это сообщение не было переслано
                if (array_key_exists("forward_from_message_id", $arrData['message'])) $forward_from_message_id = $arrData['message']['forward_from_message_id'];
                
                // From User
                $from_id    = $arrData["message"]["new_chat_participant"]["id"];
                $is_bot     = $arrData["message"]["new_chat_participant"]["is_bot"];
                $language_code = empty($arrData["message"]["new_chat_participant"]["language_code"])? '': $arrData["message"]["new_chat_participant"]["language_code"];
                $username   = empty($arrData["message"]["new_chat_participant"]["username"])? '': $arrData["message"]["new_chat_participant"]["username"];
                $first_name = empty($arrData["message"]["new_chat_participant"]["first_name"])? '': $arrData["message"]["new_chat_participant"]["first_name"];
                $last_name  = empty($arrData["message"]["new_chat_participant"]["last_name"])? '': $arrData["message"]["new_chat_participant"]["last_name"];
                
            }
            else if (array_key_exists("photo", $arrData['message'])) {
                // Проверка на приход фото
                
                // текстовое значение 
                $message = 'photo';
                
                $data_photo['file_id'] = $arrData["message"]["photo"][0]['file_id'];
                file_put_contents($this->log_file, date("Y-m-d H:i:s").' $data_photo: '.print_r($data_photo, true)."\n", FILE_APPEND);
                
                // Chat получаем id чата
                $chat_id = $arrData['message']['chat']['id'];
                
                // message получаем id
                $message_id    = $arrData['message']['message_id'];
                
                // Проверяем. Это сообщение не было переслано
                if (array_key_exists("forward_from_message_id", $arrData['message'])) $forward_from_message_id = $arrData['message']['forward_from_message_id'];
                
                // From User
                $from_id    = $arrData["message"]["from"]["id"];
                $is_bot     = $arrData["message"]["from"]["is_bot"];
                $language_code = empty($arrData["message"]["from"]["language_code"])? '': $arrData["message"]["from"]["language_code"];
                $username   = empty($arrData["message"]["from"]["username"])? '': $arrData["message"]["from"]["username"];
                $first_name = empty($arrData["message"]["from"]["first_name"])? '': $arrData["message"]["from"]["first_name"];
                $last_name  = empty($arrData["message"]["from"]["last_name"])? '': $arrData["message"]["from"]["last_name"];
            }
            else {
                return false;
            }
        } 
        // если это объект callback_query
        elseif (array_key_exists('callback_query', $arrData)) {
            if (array_key_exists("data", $arrData['callback_query'])) {
                
                // текстовое значение 
                $message = $arrData['callback_query']['data'];
                // Chat получаем id чата
                $chat_id = $arrData['callback_query']['message']['chat']['id'];
                
                // message получаем id
                $message_id    = $arrData['callback_query']['message']['message_id'];
                
                $callback_query_id = $arrData['callback_query']['id'];
                $callback_query_data = json_decode($arrData['callback_query']['data'], TRUE);
                
                // From User
                $from_id    = $arrData["callback_query"]["message"]["from"]["id"];
                $is_bot     = $arrData["callback_query"]["message"]["from"]["is_bot"];
                $language_code = empty($arrData["callback_query"]["message"]["from"]["language_code"])? '': $arrData["callback_query"]["message"]["from"]["language_code"];
                $username   = empty($arrData["callback_query"]["message"]["from"]["username"])? '': $arrData["callback_query"]["message"]["from"]["username"];
                $first_name = empty($arrData["callback_query"]["message"]["from"]["first_name"])? '': $arrData["callback_query"]["message"]["from"]["first_name"];
                $last_name  = empty($arrData["callback_query"]["message"]["from"]["last_name"])? '': $arrData["callback_query"]["message"]["from"]["last_name"];
                
                // получаем значение (название метода) под ключем 0 из callback_data кнопки inline
                //$method = current(explode("_", $this->data['callback_query']['data']));
                // вызываем переданный метод и передаем в него весь объект callback_query
                //$this->$method($this->data['callback_query']);
            }
            else {
                return false;
            }
        }
        else {
            return false;
        }
        
		// Записываем сообщение пользователя
		$data_message = $message;
		$message = mb_strtolower($message, 'utf-8');
		
		// Производим обработку полученных данных от Телеграм
		// From User
        $from_id        = (int)$from_id;
        $is_bot         = (int)$is_bot;
        $language_code  = escapeString2($language_code);
        $username       = escapeString2($username);
        $first_name     = escapeString2($first_name);
        $last_name      = escapeString2($last_name);
        
        $message        = escapeString2($message);;
        $chat_id        = (int)$chat_id;
		
		// Здесь мы просто выдаем реальные данные.
		return compact(
		    'message',
		    'message_id',
		    'chat_id',
		    'from_id',
		    'is_bot',
		    'username',
		    'first_name',
		    'last_name',
		    'language_code'
		    );
	}
}

/** Вывод инфо про ферму. Общие данные
 * @param array     $token            - ассоциативный массив
 *
 * @return int      $token['total_sumweight'] в килограммах, но 3 нуля после запятой
 * @return int      $token['total_feed_per_day']  в килограммах, но 3 нуля после запятой
 * 
 */            

function farm_information_total($token)
{
    // Параметры воды и инфо по бассейнам
    $data = function_water_parameters($token['ferma_id']);
    extract($data);
    
    $token['total_sumweight'] = 0;
    $token['total_feed_per_day'] = 0;
    
    foreach ($fish_move_select as $tank)
    {
        $token['total_sumweight']       += $tank['sumweight'];
        $token['total_feed_per_day']    += $tank['sumweight']*$tank['feeding_rate']/100;
    }
    
    $token['total_sumweight']       = round($token['total_sumweight']/1000, 3);
    $token['total_feed_per_day']    = round($token['total_feed_per_day']/1000,3);
    
    return $token;
}


/** Вывод инфо про ферму. Биомасса рыб
 * @param array     $token            - ассоциативный массив
 *
 * @return array      $fish_move_select
 * 
 */            

function farm_information_biomass($token)
{
    // Параметры воды и инфо по бассейнам
    $data = function_water_parameters($token['ferma_id']);
    extract($data);
    
    return $fish_move_select;
}



/** Проверка на наличие токена зарегистрированного у пользователя
 * @param array     $userData            - ассоциативный массив
 *
 * @return int      $token['user_id']
 * @return string   $token['display_name']  
 * @return string   $token['email']
 * @return int      $token['ferma_id']
 * @return string   $token['ferm_title']
 * @return string   $token['token']
 */            

function hasToken($userData)
{
    // Ищем токен зарегистрированный за пользователем
    $row = R::getRow( 'SELECT * FROM `telegramusers` WHERE chat_id = ? AND token != "" ORDER BY date DESC, id DESC', [ $userData['chat_id'] ]);
    if( $row ) {
        
        //Нашли токен. Теперь его надо проверить на актуальность
        // Находим информацию про Токен
        $token=Token_info($row['token']);
        return $token;
    }
    
    // Нету зарегистрированного токена
    return FALSE;
}


/** По Токену ищем информацию про ферму и владельца Токена
 * @param string    $key            Сам токен, по просто переменная
 * @param int       $pin            Флаг, что мы ищем по Пину-Алиса, а не по телеграмму. По умолчанию =0
 *
 * @return int      $token['user_id']
 * @return string   $token['display_name']  
 * @return string   $token['email']
 * @return int      $token['ferma_id']
 * @return string   $token['ferm_title']
 * @return string   $token['token']
 */            

function Token_info($key, $pin=0)
{
    $token=array();
    
    // Ищем токен
    if($pin)    $row2 = R::findOne( 'fermatoken', 'WHERE  pin_alice = ? ', [ $key ] );
    else        $row2 = R::findOne( 'fermatoken', 'WHERE  token = ? ', [ $key ] );
    
    if( $row2 ) {
        // Токен принят
        
        $token['user_id']   = $row2['user_id'];
        $token['ferma_id']  = $row2['ferma_id'];
        $token['token']     = $key;
        
        // Ищем имя и эл адрес пользователя
        $book = R::load( 'users', $token['user_id'] ); 
        if( $book ) {
            $token['display_name']  = $book['display_name'];
            $token['email']         = $book['email'];
        }
        else {
            $token['display_name']  = '';
            $token['email']         = '';
        }
        
        // Ищем название Фирмы
        $book = R::load( 'ferma', $token['ferma_id'] ); 
        if( $book ) {
            $token['ferm_title']    = $book['ferm_title'];
        }
        else {
            $token['ferm_title']    = '';
        }
        return $token;
    }
    else return FALSE;
}

/** ЗАписываем в базу данных ассоциативный массив. Записываем новое сообщение в chat_id
 * @param array $arrData - ассоциативный массив
 * @return int id - номер вставленной записи
 */
function insertUserData($arrData)
{
    $arrData['date'] = time();
    return saveRed('telegramusers', $arrData);
}

/** ЗАписываем в базу данных ассоциативный массив. Фукция insert
 * @param string $table - название базы данных
 * @param array $arrData - ассоциативный массив
 * @return int id
 */
function saveRed($table, $arrData)
{
    // Прочесть запрос от сервера и записать в файл
	//file_put_contents(WB_PATH.'/application/lib/telegram/log.txt', date("Y-m-d H:i:s").' Таблица: '.$table.'\n$arrData: '.print_r($arrData,1)."\n", FILE_APPEND);
    $tbl = R::dispense($table);
    foreach ($arrData as $name => $value){
        $tbl->$name = $value;
    }
    return R::store($tbl);
}


/** ЗАписываем в базу данных ассоциативный массив. Фукция insert
 * @param string $table - название базы данных
 * @param array $arrData - ассоциативный массив
 * @param bean $book - например так загружен $book = R::load('book', $id);
 * @return int id
 */
function updateRed($table, $book, $arrData)
{
    
    foreach ($arrData as $name => $value){
        $book->$name = $value;
    }
    return R::store($tbl);
}

// Проверка введенных текстов

function escapeString2($result)
{
    //$result = preg_replace('/(script|select|data|union|order|where|char|from|file|have|update|limit|order|outfile|set|join|create|delete|http|session)/i', '', $result);

    //$result = HtmlSpecialChars($result);
    //Удаляем все лишнии символы
    $result = preg_replace('/[^a-zа-яё0-9,\\Q.-()?_@\\E\s\n\:\/!-]+/iu', '', $result);
    
    return $result;
    
}

/**
 * 
 * https://github.com/unreal4u/telegram-api/blob/master/src/Telegram/Types/Custom/InputFile.php
 * 
 * This object represents the contents of a file to be uploaded. Must be posted using multipart/form-data in the usual
 * way that files are uploaded via the browser.
 *
 * @see https://core.telegram.org/bots/api#inputfile
 */
class InputFile
{
    /**
     * The path of the file
     * @var string
     */
    public $path = '';

    /**
     * The actual stream to the file
     * @var resource
     */
    private $stream;

    public function __construct(string $path)
    {
        $this->path = $path;
        $this->setStream();
    }

    /**
     * Will setup the stream
     *
     * @return InputFile
     * @throws FileNotReadable
     */
    private function setStream(): InputFile
    {
        if (is_readable($this->path)) {
            $this->stream = fopen($this->path, 'rb');
        } else {
            throw new FileNotReadable(sprintf('Can not read local file "%s", please check', $this->path));
        }

        return $this;
    }

    public function getStream()
    {
        $this->setStream();
        return $this->stream;
    }
}

/**
 * Посетите http://php.net/manual/en/class.curlfile.php#115161
 * https://coderoad.ru/32296272/Telegram-BOT-Api-%D0%BA%D0%B0%D0%BA-%D0%BE%D1%82%D0%BF%D1%80%D0%B0%D0%B2%D0%B8%D1%82%D1%8C-%D1%84%D0%BE%D1%82%D0%BE%D0%B3%D1%80%D0%B0%D1%84%D0%B8%D1%8E-%D1%81-%D0%BF%D0%BE%D0%BC%D0%BE%D1%89%D1%8C%D1%8E-PHP
 * 
 * Я просто меняю заголовки в этом коде для telegram бота для отправки изображения просто скопируйте эту функцию
 *
 * @see https://core.telegram.org/bots/api#inputfile
 */

// Как сделать первую букву заглавной php кирилица UTF-8
// https://htmler.ru/2016/10/26/kak-sdelat-pervuyu-bukvu-zaglovnoy-php-kirilitsa/
// пробуем кириллицу в юникоде преобразовать функцией ucfirst
// echo ucfirst($str)

if (!function_exists('mb_ucfirst') && extension_loaded('mbstring'))
{
    /**
     * mb_ucfirst - преобразует первый символ в верхний регистр
     * @param string $str - строка
     * @param string $encoding - кодировка, по-умолчанию UTF-8
     * @return string
     */
    function mb_ucfirst($str, $encoding='UTF-8')
    {
        $str = mb_ereg_replace('^[\ ]+', '', $str);
        $str = mb_strtoupper(mb_substr($str, 0, 1, $encoding), $encoding).
               mb_substr($str, 1, mb_strlen($str), $encoding);
        return $str;
    }
}
