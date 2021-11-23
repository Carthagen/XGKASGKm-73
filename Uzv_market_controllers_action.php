<?php

// Telegram Барахолка УЗВ
// Сам Бот **********
//  Канал в Телеграмме https://t.me/uzv_market
// https://akvaferma.com/start/uzv_market
//Ссылка для активации WebHooks:
   
include(WB_PATH . '/********.php');

global $row_adv, $send_data, $method, $userData, $telegramApi;

// Подключаем мой класс для Бота Телеграм
$telegramApi = new TelegramBot();

// Наш Бот
$telegramApi->token     = '*******';

$telegramApi->log_file = WB_PATH.'/logs/telegram_log_uzv_market.txt';

// Чат модератора
$chat_id_moderator = '********';

// Канал для объявлений
$chat_id_advertising = '*********';

// Получаем данные от Телеграм
// $message, $message_id, $chat_id, $from_id, $is_bot, $username, $first_name, $last_name, $language_code

$userData = $telegramApi->get_data();

if( $userData ) extract($userData);
else exit(0);

// Если сложный ответ, то, чтобы его было легче найти, то используем эту переменную
$complicated_answer = '';

// Печатаем расшифровку присланного сообщения от Телеграм $data_message, $callback_query_id, $callback_query_data, $forward_from_message_id
file_put_contents($telegramApi->log_file, date("Y-m-d H:i:s").' $data_message: '.$data_message."\n", FILE_APPEND);
file_put_contents($telegramApi->log_file, date("Y-m-d H:i:s").' $callback_query_id: '.$callback_query_id."\n", FILE_APPEND);
file_put_contents($telegramApi->log_file, date("Y-m-d H:i:s").' $callback_query_data: '.print_r($callback_query_data, TRUE)."\n", FILE_APPEND);
file_put_contents($telegramApi->log_file, date("Y-m-d H:i:s").' $forward_from_message_id: '.$forward_from_message_id."\n", FILE_APPEND);
file_put_contents($telegramApi->log_file, date("Y-m-d H:i:s").' $reply_to_message_forward_from_id: '.$reply_to_message_forward_from_id."\n", FILE_APPEND);

file_put_contents($telegramApi->log_file, date("Y-m-d H:i:s").' $userData2: '.print_r($userData, true)."\n", FILE_APPEND);

if($message == '') exit(0);


// Обрабатываем сообщение
switch ($message) {
    
    case '/start':
    case 'start':
    case 'старт':
    case '1':
        $method = 'sendMessage';
        $send_data = [
            'text' => 'Здравствуйте!',
            'parse_mode' => 'Markdown',
            'reply_markup' => [
                'resize_keyboard' => true,
                'keyboard' => [
                    [
                        ['text' => '💙 ПОДАТЬ ОБЪЯВЛЕНИЕ 💙']
                    ],
                    [
                        ['text' => '💬 ПОДДЕРЖКА 💬']
                    ]
                    
                ],
            ]
        ];
        
        // Смотрим, есть ли уже опублекованные объявления у пользователя
        $num_adv = R::count( 'telegrammarket', 'chat_id = ? AND status = 2', [ $userData['chat_id'] ]);
        if( $num_adv ) {
            $send_data['reply_markup']['keyboard'][1][0] = ['text' => '⭕️ УДАЛИТЬ ОБЪЯВЛЕНИЕ ⭕️'];
            $send_data['reply_markup']['keyboard'][2][0] = ['text' => '💬 ПОДДЕРЖКА 💬'];
        }
        
        if( $first_name ) $send_data['text'] = '*Здравствуйте, '.$first_name.'!* 🤝';
        
        
        $send_data['text'] .= "\n\nЯ *Барахолка Бот* помогу Вам опубликовать объявление в канале *УЗВ Барахолка:*\n\n"
        ."👇 Выберите нужное меню и следуйте моим инструкциям.\n\n"
        .'❓ В случае возникновения проблем или трудностей - свяжитесь с поддержкой';
        break;
        
    case 'отмена':
    case 'cancel':
    case '2':
        $method = 'sendMessage';
        $send_data = [
            'text' => 'Отменено.',
            'parse_mode' => 'Markdown',
            'reply_markup' => [
                'resize_keyboard' => true,
                'keyboard' => [
                    [
                        ['text' => '💙 ПОДАТЬ ОБЪЯВЛЕНИЕ 💙']
                    ],
                    [
                        ['text' => '💬 ПОДДЕРЖКА 💬']
                    ]
                    
                ],
            ]
        ];
        
        // Смотрим, есть ли уже опублекованные объявления у пользователя
        $num_adv = R::count( 'telegrammarket', 'chat_id = ? AND status = 2', [ $userData['chat_id'] ]);
        if( $num_adv ) {
            $send_data['reply_markup']['keyboard'][1][0] = ['text' => '⭕️ УДАЛИТЬ ОБЪЯВЛЕНИЕ ⭕️'];
            $send_data['reply_markup']['keyboard'][2][0] = ['text' => '💬 ПОДДЕРЖКА 💬'];
        }
        
        // Ищем не закрытые объявления и их закрываем на модерацию, чтобы нельзя было далее править их.
        $row_adv = R::findOne( 'telegrammarket', 'chat_id = ? AND status = 0 ORDER BY date DESC, id DESC', [ $userData['chat_id'] ]);
        if( $row_adv ) {
            $row_adv -> status = 4;
            R::store($row_adv);
        }
        
        break;
        
    case ' поддержка ':
    case 'поддержка':
    case '/help':
    case 'help':
    case 'справка':
    case 'помощь':
    case 'помощ':
    case '3':
        
        // Смотрим, есть ли уже опублекованные объявления у пользователя?
        $num_adv_opu = R::count( 'telegrammarket', 'chat_id = ? AND status = 2', [ $userData['chat_id'] ]);
        // На модерации?
        $num_adv_mod = R::count( 'telegrammarket', 'chat_id = ? AND status = 1', [ $userData['chat_id'] ]);
        
        $method = 'sendPhoto';
        $send_data = [
            'caption' => "🤖 Бот-администратор канала УЗВ Барахолка • @uzv_market\n"
            ."🤓 Я приму у тебя объявление и опубликую его на канале.\n\n"
            ."📗 Опублековано объявлений: $num_adv_opu\n"
            ."📒 На модерации объявлений: $num_adv_mod\n\n"
            
            ."☀️Команды\n"
            ."1️⃣Старт - начать все с начало.\n"
            ."2️⃣Отмена - начать все с начало.\n"
            ."3️⃣Помощь - справочная информация.\n"
            ."4️⃣Подать объявление - подать объявление в канал УЗВ Барахолка.\n"
            ."5️⃣Удалить объявление - удалить ранее поданное объявление.\n"
            ."6️⃣Чат - написать сообщение модератору.\n\n"
            
            ."❓ Есть вопросы или трудности, напиши или позвони админу. \n\n"
            ."Буду рад услышать замечания, коментарии и идеи по развитию канала: @Vasiliy861\n\n",
            
            'photo' => 'AgACAgQAAxkBAAMLYYWHeEkSQqwdPDg8oClnEF8SyMUAAnu3MRu72iBQei25_817dUUBAAMCAANtAAMiBA'
        ];
        break; 
    
    // ' подать объявление ' - Менять нельзя!! по этой фразе индентифицируется в базе данных шаги действий по публекации объявления
    case ' подать объявление ':
    case 'подать объявление':
    case 'подать обьявление':
    case '4':
    
        $method = 'sendMessage';
        $send_data = [
            'text' => "⚡️ Вы находитесь в меню подачи объявлений\n\n"
            ."💬 Ответьте на несколько моих вопросов и я размещу Ваше объявление на канале *УЗВ Барахолка*\n\n"
            ."⚠️ В одном объявлении можно указать только 1 товар!\n\n"
            ."👇👇👇\n\n"
            ."1⃣ Напишите кратко что Вы продаёте\n\n"
            ."(информация пойдёт в заголовок объявления)"
            ,
            'parse_mode' => 'Markdown',
            'reply_markup' => [
                'remove_keyboard' => true,
            ]
        ];
        $complicated_answer = 'ответ1';
        
        break; 
        
    // Удалить объявление
    case ' удалить объявление ':
    case 'удалить объявление':
    case 'удалить обьявление':
    case 'удалить':
    case '5':
    
        $method = 'sendMessage';
        $send_data = [
            'text' => "⚡️ Вы находитесь в меню удаления объявлений\n\n"
            ."💬 Ответьте на несколько моих вопросов и я удалю Ваше объявление из канала *УЗВ Барахолка*\n\n"
            
            ."👇👇👇\n\n"
            ."1⃣Какое объявление удалить?\n\n"
            ."(Перешлите сюда свое объявление из канала *УЗВ Барахолка*)"
            ,
            'parse_mode' => 'Markdown',
            'reply_markup' => [
                'resize_keyboard' => true,
                'keyboard' => [
                    [
                        ['text' => 'Отмена']
                    ]
                ],
            ]
        ];
        
        $complicated_answer = 'del1';
        
        break; 
    
    // Пришли фотографии от пользователя
    case 'photo':
        
        //  УДАЛЕНИЕ! Переслано ли сообщение или нет? Готовимся это сообщение удалить
        $row = R::findOne( 'telegramusers', 'chat_id = ? ORDER BY date DESC, id DESC', [ $userData['chat_id'] ]);
        if( $row AND $row['answer'] == 'del1' AND $forward_from_message_id !='' ) {
            $row_adv = R::findOne( 'telegrammarket', 'chat_id = ? AND message_id = ? ORDER BY date DESC, id DESC', [ $userData['chat_id'], $forward_from_message_id ]);
            if( $row_adv AND $row_adv['status'] == 2 ) {
                
                // ЗАписываем в базе данных, что сообщение удалено
                $row_adv -> status = 4;
                R::store($row_adv);
            
                $method = 'deleteMessage';
                $send_data = [
                    'chat_id' => $chat_id_advertising,
                    'message_id' => $forward_from_message_id,
                ];
                
                // Отправляем удалить сообщение на канале
                $response_result = $telegramApi->sendRequest($method, $send_data);
                
                $response_result = $telegramApi->sendRequest($method, $send_data, $userData['first_name']);
                if($response_result['ok']) {
                    send_ok();
                } else {
                    send_false();
                }
                
                exit(0);
            } else {
                $method = 'sendMessage';
                $send_data = [
                    'chat_id' => $userData['chat_id'],
                    'text' => '⁉️ Такое объявление, Вы мой дорого друг, не публековали! :('
                ];
                // Отправляем сообщение пользователю в чат
                $telegramApi->sendRequest($method, $send_data);
                exit(0);
            }
        }
        
        // Фотография прием
        // Ответ №2
        if($row AND $row['answer'] == 'ответ2') {
            
            // Ждем в следующем посте новую фотографию
            $complicated_answer = 'ответ2';
            
            $row_adv = R::findOne( 'telegrammarket', 'chat_id = ? ORDER BY date DESC, id DESC', [ $userData['chat_id'] ]);
            if( $row_adv AND $row_adv['photo1'] == '' AND $row_adv['status'] == 0 ) {
                $row_adv -> photo1 = $data_photo['file_id'];
                R::store($row_adv);
                
                // Есл сложный ответ и нам надо его поймать, то
                if($complicated_answer != '' ) $userData['answer'] = $complicated_answer;
                
                // зменить имя ключа в массиве Do not use field names ending with _id, these are reserved for bean relations.
                $userData['messageid'] = $userData['message_id'];
                unset($userData['message_id']);
                
                insertUserData($userData);
                exit(0);
            }
            elseif( $row_adv AND $row_adv['photo2'] == ''  AND $row_adv['status'] == 0 ) {
                $row_adv -> photo2 = $data_photo['file_id'];
                R::store($row_adv);
                
                // Есл сложный ответ и нам надо его поймать, то
                if($complicated_answer != '' ) $userData['answer'] = $complicated_answer;

                // зменить имя ключа в массиве Do not use field names ending with _id, these are reserved for bean relations.
                $userData['messageid'] = $userData['message_id'];
                unset($userData['message_id']);
                
                insertUserData($userData);
                exit(0);
            }
            elseif( $row_adv AND $row_adv['photo3'] == ''  AND $row_adv['status'] == 0 ) {
                $row_adv -> photo3 = $data_photo['file_id'];
                R::store($row_adv);
                
            } else {
                exit(0);
            }
            
        }
        
        break; 
        
    // Все фотографии получены, переходим к ЗАданию №3
    // Вопрос №3
    case 'готово':
        
        $row_adv = R::findOne( 'telegrammarket', 'chat_id = ? ORDER BY date DESC, id DESC', [ $userData['chat_id'] ]);
        if( $row_adv AND $row_adv['photo1'] != ''  AND $row_adv['status'] == 0 ) {
            $method = 'sendMessage';
            $send_data = [
                'text' => "3️⃣ В какой Стране находитесь?\n\n"
                ."(укажем в объявлении как Страна происхождения товара)",
                'reply_markup' => [
                    'resize_keyboard' => true,
                    'keyboard' => [
                        [
                            ['text' => 'Отмена']
                        ]
                    ],
                ]
            ];
            
            
        } else {
            $method = 'sendMessage';
            $send_data = [
                'text' => "Вы не прислали не одной картинки!?\n\n"
                ."Пришлите фотографии продукта, а потом нажмите  *ГОТОВО* или наберите слово: *ГОТОВО*",
                'parse_mode' => 'Markdown',
                'reply_markup' => [
                    'resize_keyboard' => true,
                    'keyboard' => [
                        [
                            ['text' => 'Готово']
                        ],  
                        [
                            ['text' => 'Отмена']
                        ]
                    ],
                ]
            ];
        }
        
        
        break; 
    
        
    // Послать письмо Модератору или Пользователю или всем пользователям
    case 'чат':
    case 'chat':
    case '6':
    
        $method = 'sendMessage';
        $send_data = [
            'text' => "📝 Вы находитесь в меню переписка в чате\n\n"
            ."1️⃣ Кому написать сообщение?",
            'parse_mode' => 'Markdown',
            'reply_markup' => [
                'resize_keyboard' => true,
                'keyboard' => [
                    [
                        ['text' => 'Отмена'],
                        ['text' => 'Модератору']
                    ]
                ],
            ]
        ];
        
        $complicated_answer = 'chat';
        
        break; 
        
    // Послать письмо Модератору
    case 'модератору':
    case 'модератору':
    case 'moderator':
        $method = 'sendMessage';
        $send_data = [
            'text' => "2️⃣Ваше сообщение",
            'reply_markup' => [
                'resize_keyboard' => true,
                'keyboard' => [
                    [
                        ['text' => 'Отмена']
                    ]
                ],
            ]
        ];
        
        $complicated_answer = 'moderator_message';
        
        break; 
        
    
        
    default:
        $method = 'sendMessage';
        $send_data = [
            'text' => 'Не понимаю о чем Вы :('
        ];
        
        // Moderator
        // Ловим нажатия модератора на кнопку опублековать или отклонить
        if($callback_query_id AND $chat_id == $chat_id_moderator ) {
            $method = 'answerCallbackQuery';
            $send_data = [
                'callback_query_id' => $callback_query_id,
                'text' => "Спасибо! Вы поставили " . $callback_query_data['action'],
            ];
            
            // Добавляем данные модератора 
            $send_data['chat_id']       = $chat_id;
            
            // Модератор нажал Опублековать
            if($callback_query_data['action'] == 'publish') {
                
                // Проверяем. Есть ли не опубликованное объявление. Оно отправлено на модерацию?
                $row_adv = R::findOne( 'telegrammarket', 'chat_id = ? AND id = ? ORDER BY date DESC, id DESC', [ $callback_query_data['chat_id'], $callback_query_data['market_id'] ]);
                if( $row_adv AND $row_adv['status'] == 1 ) {
                    $row_adv->status = 2;
                    R::store($row_adv);
                    
                    // Отправляем ответ модератору, о том, какую оценку он поставил
                    $telegramApi->sendRequest($method, $send_data);
                    
                    // У Модератора редактируем сообщение. Убираем кнопки и пишем текст, что он нажал
                    $method = 'editMessageText';
                    $send_data = [
                        'chat_id' => $chat_id_moderator,
                        'message_id' => $message_id,
                        'text' => "Вы поставили *". $callback_query_data['action'].'*',
                        'parse_mode' => 'Markdown',
                    ];
                    
                    // Отправляем ответ модератору, отредактированное сообщение
                    $telegramApi->sendRequest($method, $send_data);
                    
                    // Подготавливаем рекламное объявление к публикации
                    // Добавляем данные пользователя 
                    $send_data = [];
                    $send_data['chat_id']       = $chat_id_advertising;
                    send_advertising();
                    
                    // Отправляем в канал Объявление и получаем ответ от Телеграмма. Нам нужен номер поста или сообщения message_id
                    $response_result = $telegramApi->sendRequest($method, $send_data, $userData['first_name']);
                    if($response_result['ok']) {
                        
                        $row_adv->message_id = $response_result['result'][0]['message_id'];
                        R::store($row_adv);
                        
                        // Пишем письмо пользователю о том, что его объявление опублековано на канале
                        $method = 'sendMessage';
                        $send_data = [
                            'text' => "👍 Ваше объявление опубликовано на канале *УЗВ Барахолка*",
                            'parse_mode' => 'Markdown',
                            'reply_markup' => [
                                'resize_keyboard' => true,
                                'keyboard' => [
                                    [
                                        ['text' => '💙 ПОДАТЬ ОБЪЯВЛЕНИЕ 💙']
                                    ],
                                    [
                                        ['text' => '💬 ПОДДЕРЖКА 💬']
                                    ]
                                ],
                            ]
                        ];
            
                        // Смотрим, есть ли уже опублекованные объявления у пользователя
                        $num_adv = R::count( 'telegrammarket', 'chat_id = ? AND status = 2', [ $userData['chat_id'] ]);
                        if( $num_adv ) {
                            $send_data['reply_markup']['keyboard'][1][0] = ['text' => '⭕️ УДАЛИТЬ ОБЪЯВЛЕНИЕ ⭕️'];
                            $send_data['reply_markup']['keyboard'][2][0] = ['text' => '💬 ПОДДЕРЖКА 💬'];
                        }
                        $send_data['chat_id']       = $callback_query_data['chat_id'];
                        
                        // Отправляем ответ пользователю
                        $telegramApi->sendRequest($method, $send_data);
                        
                        
                    } else {
                        $method = 'sendMessage';
                        $send_data = [
                            'chat_id' => $userData['chat_id'],
                            'text' => '⁉️ Произошла ошибка! :('
                        ];
                        // Отправляем сообщение пользователю в чат
                        $telegramApi->sendRequest($method, $send_data);
                    }
                    
                    exit(0);
                    
                } else {
                    // Нету объявлений. Все опублекованы уже
                    $send_data['text'] = 'Нету объявления для модерации!';
                    $send_data['show_alert'] = true;
                    
                    // Отправляем ответ модератору
                    $telegramApi->sendRequest($method, $send_data);
                    exit(0);
                }
            }
            
            // Модератор нажал Отклонить
            elseif($callback_query_data['action'] == 'reject') {
                // Проверяем. Есть ли не опублекованное объявление. Оно отправлено на модерацию?
                $row_adv = R::findOne( 'telegrammarket', 'chat_id = ? AND id = ? ORDER BY date DESC, id DESC', [ $callback_query_data['chat_id'], $callback_query_data['market_id'] ]);
                if( $row_adv AND $row_adv['status']==1) {
                    $row_adv->status = 3;
                    R::store($row_adv);
                    
                    // Отправляем ответ модератору, о том, какую оценку он поставил
                    $telegramApi->sendRequest($method, $send_data);
                    
                    // У Модератора редактируем сообщение. Убираем кнопки и пишем текст, что он нажал
                    $method = 'editMessageText';
                    $send_data = [
                        'chat_id' => $chat_id_moderator,
                        'message_id' => $message_id,
                        'text' => "Вы поставили *". $callback_query_data['action'].'*',
                        'parse_mode' => 'Markdown',
                    ];
                    
                    // Отправляем ответ модератору, отредактированное сообщение
                    $telegramApi->sendRequest($method, $send_data);
                    
                    // Пишем письмо пользователю о том, что его объявление отклонено
                    $method = 'sendMessage';
                    $send_data = [
                        'text' => "👎 Я сожалею, Ваше объявление отклонено для публикации на канале *УЗВ Барахолка*",
                        'parse_mode' => 'Markdown',
                        'reply_markup' => [
                            'resize_keyboard' => true,
                            'keyboard' => [
                                [
                                    ['text' => '💙 ПОДАТЬ ОБЪЯВЛЕНИЕ 💙']
                                ],
                                [
                                    ['text' => '💬 ПОДДЕРЖКА 💬']
                                ]
                            ],
                        ]
                    ];
        
                    // Смотрим, есть ли уже опубликованные объявления у пользователя
                    $num_adv = R::count( 'telegrammarket', 'chat_id = ? AND status = 2', [ $userData['chat_id'] ]);
                    if( $num_adv ) {
                        $send_data['reply_markup']['keyboard'][1][0] = ['text' => '⭕️ УДАЛИТЬ ОБЪЯВЛЕНИЕ ⭕️'];
                        $send_data['reply_markup']['keyboard'][2][0] = ['text' => '💬 ПОДДЕРЖКА 💬'];
                    }
                    $send_data['chat_id']       = $callback_query_data['chat_id'];
                    
                    // Отправляем ответ пользователю
                    $telegramApi->sendRequest($method, $send_data);
                    
                    exit(0);
                    
                } else {
                    // Нету объявлений. Все опублекованы уже
                    $send_data['text'] = 'Нету объявления для модерации!';
                    $send_data['show_alert'] = true;
                    
                    // Отправляем ответ модератору
                    $telegramApi->sendRequest($method, $send_data);
                    exit(0);
                }
            }
        } 
        // Если это не модерация, то разбираем, что за вопрос пришел.
        else {
        
        
        //
        // Если начали присылать ответы на бота по поводу объявления. Проверяем
        $row = R::findOne( 'telegramusers', 'chat_id = ? ORDER BY date DESC, id DESC', [ $chat_id ]);
        
        // Послать письмо Модератору
        if( $row AND $row['answer'] == 'moderator_message' ) {
            
            // Пересылаем модератору письмо от пользователя. Тогда Модератор сможет посмотреть профиль пользователя
            $method = 'forwardMessage';
            $send_data = [
                'chat_id' => $chat_id_moderator,
                'from_chat_id' => $chat_id,
                'message_id' => $message_id
            ];
            
            // Отправляем сообщение Модератору
            $response_result = $telegramApi->sendRequest($method, $send_data, $userData['first_name']);
            if($response_result['ok']) {
                
                send_ok();
            
            } else {
                
                send_false();
            }
            
            exit(0);
        }
        
        // Послать письмо Пользователю от Модератора. Получили номер Пользователя от Модератора
        elseif( $row AND $row['answer'] == 'chat' ) {
            
            // Получаем информацию о пользователе Канала. 
            $method = 'getChatMember';
            $send_data = [
                'chat_id' => $chat_id_advertising,
                'user_id' => $message
            ];
            
            // Пользователь с таким номером принадлежит нашему Каналу или нет?
            $response_result = $telegramApi->sendRequest($method, $send_data, $userData['first_name']);
            if($response_result['ok']) {
                
                // Пересылаем модератору запрос на отправку Сообщения
                $method = 'sendMessage';
                $send_data = [
                    'chat_id' => $chat_id,
                    'text' => "Пользователь с таким номером есть в нашем Канале\nВведите само сообщение:",
                    'reply_markup' => [
                        'resize_keyboard' => true,
                        'keyboard' => [
                            [
                                ['text' => 'Отмена']
                            ]
                        ],
                    ]
                ];
                
                $complicated_answer = 'chat_message';
                
            } else {
                
                // Пересылаем модератору письмо, что пользователь с таким номером не сущетвует на Канале
                $method = 'sendMessage';
                $send_data = [
                    'chat_id' => $chat_id,
                    'text' => "Пользователь с таким номером нет на нашем Канале\nВведите заново номер Пользователя:",
                    'reply_markup' => [
                        'resize_keyboard' => true,
                        'keyboard' => [
                            [
                                ['text' => 'Отмена']
                            ]
                        ],
                    ]
                ];
                
                $complicated_answer = 'chat';
                
            }
        }
        // Послать письмо Пользователю, по его номеру. Продолжение
        elseif( $row AND $row['answer'] == 'chat_message' ) {
            
            // Пересылаем модератору письмо от пользователя. Тогда Модератор сможет посмотреть профиль пользователя
            $method = 'sendMessage';
            $send_data = [
                'chat_id' => $row['message'],
                'text' => $data_message
            ];
            
            // Отправляем сообщение Модератору
            $response_result = $telegramApi->sendRequest($method, $send_data, $userData['first_name']);
            if($response_result['ok']) {
                
                send_ok();
            
            } else {
                
                send_false();
            }
            
            exit(0);
        }
        
        
        //
        // Модератор прислал ответ на чьето письмо
        elseif( $reply_to_message_forward_from_id != '' ) {
            // Пересылаем модератору письмо от пользователя. Тогда Модератор сможет посмотреть профиль пользователя
            $method = 'sendMessage';
            $send_data = [
                'chat_id' => $reply_to_message_forward_from_id,
                'text' => $data_message
            ];
            
            // Отправляем сообщение Пользователю которому предназначено сообщение
            $response_result = $telegramApi->sendRequest($method, $send_data, $userData['first_name']);
            if($response_result['ok']) {
            
                send_ok();
            } else {
                
                send_false();
            }
            
            
            exit(0);
        }
        
        // Вопрос №1
        elseif( $row AND ( $row['message'] == ' подать объявление ' OR $row['answer'] == 'ответ1' ) ) {
            
            // Очищаем сообщение
            $message = $data_message;
            
            if($message != '') {
                
                $answer = ['chat_id' => $chat_id, 'date' => time(), 'header' => $message, 'is_bot' => $is_bot, 'username' => $username, 'first_name' => $first_name, 'last_name' => $last_name, 'language_code' => $language_code];
                saveRed('telegrammarket', $answer);
                $send_data = [
                    'text' => "2️⃣ Добавьте фото\n\n(можно не более 3-х, добавить их нужно одним сообщением, а после обязательно нажмите кнопку *ГОТОВО* или наберите слово: *ГОТОВО*",
                    'parse_mode' => 'Markdown',
                    'reply_markup' => [
                        'resize_keyboard' => true,
                        'keyboard' => [
                            [
                                ['text' => 'Готово']
                            ],
                            [
                                ['text' => 'Отмена']
                            ]
                        ],
                    ]
                ];
            }
            else {
                $send_data = [
                    'text' => "Ошибка добавления Заголовка!\nДавайте попробуем еще."
                ];
                $complicated_answer = 'ответ1';
            }
            $complicated_answer = 'ответ2';
        }
        
        // Вопрос №4
        elseif( $row AND ($row['message'] == 'готово' OR $row['answer'] == 'ответ3' ) ) {
            
            // Очищаем сообщение и делаем первую букву заглавную
            $message = $data_message;
            
            if($message != '') {
                
                $row_adv = R::findOne( 'telegrammarket', 'chat_id = ? ORDER BY date DESC, id DESC', [ $userData['chat_id'] ]);
                if( $row_adv AND $row_adv['status'] == 0 ) {
                    $row_adv->country = $message;
                    R::store($row_adv);
                
                    $method = 'sendMessage';
                    $send_data = [
                        'text' => "4️⃣В каком городе находитесь?\n\n"
                        ."(укажем в объявлении как город где продается товар)",
                        'reply_markup' => [
                            'resize_keyboard' => true,
                            'keyboard' => [
                                [
                                    ['text' => 'Отмена']
                                ]
                            ],
                        ]
                    ];
                    $complicated_answer = 'ответ4';
                    
                } else {
                    $send_data = [
                        'text' => "Ошибка добавления Страны!\nДавайте попробуем еще."
                    ];
                    $complicated_answer = 'ответ3';
                }
                
            } else {
                $send_data = [
                    'text' => "Ошибка добавления Страны!\nДавайте попробуем еще."
                ];
                $complicated_answer = 'ответ3';
            }
        }
        
        
        // Вопрос №5
        elseif( $row AND $row['answer'] == 'ответ4' ) {
            
            // Очищаем сообщение и делаем первую букву заглавную
            $message = $data_message;
            
            if($message != '') {
                
                $row_adv = R::findOne( 'telegrammarket', 'chat_id = ? ORDER BY date DESC, id DESC', [ $userData['chat_id'] ]);
                if( $row_adv  AND $row_adv['status'] == 0 ) {
                    $row_adv->town = $message;
                    R::store($row_adv);
                
                    $method = 'sendMessage';
                    $send_data = [
                        
                        'text' => "5️⃣ За сколько продаёте?\n\n"
                        ."(укажем в стоимости)",
                        'reply_markup' => [
                            'resize_keyboard' => true,
                            'keyboard' => [
                                [
                                    ['text' => 'Отмена']
                                ]
                            ],
                        ]
                    ];
                    $complicated_answer = 'ответ5';
                    
                } else {
                    $send_data = [
                        'text' => "Ошибка добавления Города!\nДавайте попробуем еще."
                    ];
                    $complicated_answer = 'ответ4';
                }
                
            } else {
                $send_data = [
                    'text' => "Ошибка добавления Города!\nДавайте попробуем еще."
                ];
                $complicated_answer = 'ответ4';
            }
        }
        
        
        // Вопрос №6
        elseif( $row AND $row['answer'] == 'ответ5' ) {
            
            // Очищаем сообщение 
            $message = $data_message;
            
            if($message != '') {
                
                $row_adv = R::findOne( 'telegrammarket', 'chat_id = ? ORDER BY date DESC, id DESC', [ $userData['chat_id'] ]);
                if( $row_adv AND $row_adv['status'] == 0 ) {
                    $row_adv->price = $message;
                    R::store($row_adv);
                
                    $method = 'sendMessage';
                    $send_data = [
                        
                        'text' => "6️⃣ Расскажите подробнее для потенциальных клиентов о Вашем товаре\n\n"
                        ."(информация пойдет в текст объявления)\n"
                        ."Не пишите много! Телеграмм ограничивает общий объем текста под фотографией.",
                        'reply_markup' => [
                            'resize_keyboard' => true,
                            'keyboard' => [
                                [
                                    ['text' => 'Отмена']
                                ]
                            ],
                        ]
                    ];
                    $complicated_answer = 'ответ6';
                    
                } else {
                    $send_data = [
                        'text' => "Ошибка добавления Цены!\nДавайте попробуем еще."
                    ];
                    $complicated_answer = 'ответ5';
                }
                
            } else {
                $send_data = [
                    'text' => "Ошибка добавления Цены!\nДавайте попробуем еще."
                ];
                $complicated_answer = 'ответ5';
            }
        }
        
        
        // Вопрос №7
        elseif( $row AND $row['answer'] == 'ответ6' ) {
            
            // Очищаем сообщение и берем сырое сообщение, без изменений. Уменьшения всех букв.
            $message = $data_message;
            
            if($message != '') {
                
                $row_adv = R::findOne( 'telegrammarket', 'chat_id = ? ORDER BY date DESC, id DESC', [ $userData['chat_id'] ]);
                if( $row_adv  AND $row_adv['status'] == 0 ) {
                    $row_adv->contents = $message;
                    R::store($row_adv);
                
                    $method = 'sendMessage';
                    $send_data = [
                        
                        'text' => "7️⃣ Давайте определимся с темой объявления?\n\n"
                        ."(нажмите нужную кнопку и мы добавим тему в объявление)",
                        'reply_markup' => [
                            'resize_keyboard' => true,
                            'keyboard' => [
                                [
                                    ['text' => 'Бассейны'],
                                    ['text' => 'Мехфильтры'],
                                    ['text' => 'Биофильтры'],
                                ],
                                [
                                    ['text' => 'Воздух'],
                                    ['text' => 'Кислород'],
                                    ['text' => 'Озон'],
                                ],
                                [
                                    ['text' => 'УЗВ'],
                                    ['text' => 'Автоматика'],
                                    ['text' => 'Кормушки'],
                                ],
                                [
                                    ['text' => 'Рыба'],
                                    ['text' => 'Корма'],
                                    ['text' => 'Инкубаторы'],
                                ]
                            ],
                        ]
                    ];
                    $complicated_answer = 'ответ7';
                    
                } else {
                    $send_data = [
                        'text' => "Ошибка добавления Описания!\nДавайте попробуем еще."
                    ];
                    $complicated_answer = 'ответ6';
                }
                
            } else {
                $send_data = [
                    'text' => "Ошибка добавления Описания!\nДавайте попробуем еще."
                ];
                $complicated_answer = 'ответ6';
            }
        }
        
        // Вопрос №8
        elseif( $row AND $row['answer'] == 'ответ7' ) {
            
            // Очищаем сообщение и берем сырое сообщение, без изменений. Уменьшения всех букв.
            $message = $data_message;
            
            if($message == 'Бассейны' OR $message == 'Мехфильтры' OR $message == 'Биофильтры' OR $message == 'Воздух' OR $message == 'Кислород' OR $message == 'Озон' OR $message == 'УЗВ' OR $message == 'Автоматика' OR $message == 'Кормушки' OR $message == 'Рыба' OR $message == 'Корма' OR $message == 'Инкубаторы') {
                
                $row_adv = R::findOne( 'telegrammarket', 'chat_id = ? ORDER BY date DESC, id DESC', [ $userData['chat_id'] ]);
                if( $row_adv  AND $row_adv['status'] == 0 ) {
                    $row_adv->topic = $message;
                    R::store($row_adv);
                
                    $method = 'sendMessage';
                    $send_data = [
                        
                        'text' => "8️⃣ Можете ли Вы доставить товар покупателю?\n\n"
                        ."(нажмите нужную кнопку и мы добавим информацию о доставке в объявление)",
                        'reply_markup' => [
                            'resize_keyboard' => true,
                            'keyboard' => [
                                [
                                    ['text' => 'Доставки нет']
                                ],
                                [
                                    ['text' => 'Доставка по стране']
                                ],
                                [
                                    ['text' => 'Доставка по Миру']
                                ],
                                [
                                    ['text' => 'Доставка по договоренности']
                                ],
                            ],
                        ]
                    ];
                    $complicated_answer = 'ответ8';
                    
                } else {
                    $send_data = [
                        'text' => "Ошибка добавления Описания!\nДавайте попробуем еще."
                    ];
                    $complicated_answer = 'ответ7';
                }
                
            } else {
                $send_data = [
                    'text' => "Ошибка добавления Описания!\nДавайте попробуем еще."
                ];
                $complicated_answer = 'ответ7';
            }
        }
        
        
        // Вопрос №9
        elseif( $row AND $row['answer'] == 'ответ8' ) {
            
            // Очищаем сообщение и берем сырое сообщение, без изменений. Уменьшения всех букв.
            $message = $data_message;
            
            if($message == 'Доставки нет' OR $message == 'Доставка по стране' Or $message == 'Доставка по Миру' Or $message == 'Доставка по договоренности') {
                
                $row_adv = R::findOne( 'telegrammarket', 'chat_id = ? ORDER BY date DESC, id DESC', [ $userData['chat_id'] ]);
                if( $row_adv  AND $row_adv['status'] == 0 ) {
                    $row_adv->delivery = $message;
                    R::store($row_adv);
                
                    $method = 'sendMessage';
                    $send_data = [
                        
                        'text' => "9️⃣ Как с Вами связаться клиенту?\n\n"
                        ."(можно указать телефон, Telegram, Whats app, Viber, страницу в ВК и т.п.)\n\n"
                        ."Пример:\n"
                        ."Telegram: @Username\n"
                        ."Телефон: +7(916)777-00-00",
                        'reply_markup' => [
                            'resize_keyboard' => true,
                            'keyboard' => [
                                [
                                    ['text' => 'Отмена']
                                ],
                            ],
                        ]
                    ];
                    $complicated_answer = 'ответ9';
                    
                } else {
                    $send_data = [
                        'text' => "Ошибка добавления Доставки!\nДавайте попробуем еще."
                    ];
                    $complicated_answer = 'ответ8';
                }
                
            } else {
                $send_data = [
                    'text' => "Ошибка добавления Доставки!\nДавайте попробуем еще."
                ];
                $complicated_answer = 'ответ8';
            }
        }
        
        
        
        
        
        // Вопрос №10
        // Вопрос №10
        elseif( $row AND $row['answer'] == 'ответ9' ) {
            
            // Очищаем сообщение 
            $message = $data_message;
            
            if($message != '') {
                
                $row_adv = R::findOne( 'telegrammarket', 'chat_id = ? ORDER BY date DESC, id DESC', [ $userData['chat_id'] ]);
                if( $row_adv AND !$row_adv['status']) {
                    $row_adv->contacts = $message;
                    $row_adv->status = 1;
                    R::store($row_adv);
                
                    // Теперь отсылаем объявление на модерацию
                    // Добавляем данные пользователя 
                    $send_data = [];
                    $send_data['chat_id']       = $chat_id_moderator;
                    
                    // Формируем рекламное объявление
                    send_advertising();        
                                
                    
                    // Пересылаем модератору объявление
                    // Отправляем ответ с двумя кнопками: Отклонить или Обуликовать Объявление
                    $response_result = $telegramApi->sendRequest($method, $send_data, $userData['first_name']);
                    if($response_result['ok']) {
                        
                        $method = 'sendMessage';
                        $send_data = [
                            'chat_id' => $chat_id,
                            'text' => "🤝 Объявление принято и после модерации будет размещено на нашем канале: @uzv_market в порядке очереди!\n\n"
                            ."🤷‍♀ Если Вы не указали контакты для связи или другую информацию, то объявление попадет в спам и не будет опубликовано.\n\n"
                            ."⚠️ Если Ваше объявление не было опубликовано на канале в течение 12 часов, свяжитесь с админом: @Vasiliy861, ведь что-то пошло не так.",
                            'reply_markup' => [
                                'resize_keyboard' => true,
                                'keyboard' => [
                                    [
                                        ['text' => '💙 ПОДАТЬ ОБЪЯВЛЕНИЕ 💙']
                                    ],
                                    [
                                        ['text' => '💬 ПОДДЕРЖКА 💬']
                                    ]
                                ],
                            ]
                        ];
                        
                        // Смотрим, есть ли уже опублекованные объявления у пользователя
                        $num_adv = R::count( 'telegrammarket', 'chat_id = ? AND status = 2', [ $userData['chat_id'] ]);
                        if( $num_adv ) {
                            $send_data['reply_markup']['keyboard'][1][0] = ['text' => '⭕️ УДАЛИТЬ ОБЪЯВЛЕНИЕ ⭕️'];
                            $send_data['reply_markup']['keyboard'][2][0] = ['text' => '💬 ПОДДЕРЖКА 💬'];
                        }
            
                        // Отправляем ответ пользователю
                        $telegramApi->sendRequest($method, $send_data, $userData['first_name']);
                        
                        
                    } else {
                        
                        $method = 'sendMessage';
                        $send_data = [
                            'chat_id' => $chat_id,
                            'text' => "⁉️ Произошла ошибка. Ваше объявление не отослано на модерацию! :(",
                            'reply_markup' => [
                                'resize_keyboard' => true,
                                'keyboard' => [
                                    [
                                        ['text' => '💙 ПОДАТЬ ОБЪЯВЛЕНИЕ 💙']
                                    ],
                                    [
                                        ['text' => '💬 ПОДДЕРЖКА 💬']
                                    ]
                                ],
                            ]
                        ];
                        
                        // Смотрим, есть ли уже опублекованные объявления у пользователя
                        $num_adv = R::count( 'telegrammarket', 'chat_id = ? AND status = 2', [ $userData['chat_id'] ]);
                        if( $num_adv ) {
                            $send_data['reply_markup']['keyboard'][1][0] = ['text' => '⭕️ УДАЛИТЬ ОБЪЯВЛЕНИЕ ⭕️'];
                            $send_data['reply_markup']['keyboard'][2][0] = ['text' => '💬 ПОДДЕРЖКА 💬'];
                        }
            
                        
                        // Отправляем ответ пользователю
                        $telegramApi->sendRequest($method, $send_data, $userData['first_name']);
                        
                        
                    }
                    
                    // Пересылаем модератору письмо от пользователя. Тогда Модератор сможет посмотреть профиль пользователя
                    $method = 'forwardMessage';
                    $send_data = [
                        'chat_id' => $chat_id_moderator,
                        'from_chat_id' => $chat_id,
                        'message_id' => $message_id,
                        'disable_notification' => true
                    ];
                    
                    // Пересылаем модератору письмо от пользователя.
                    $telegramApi->sendRequest($method, $send_data);
                    
                    // Отправляем ответ с двумя кнопками: Отклонить или Обуликовать Объявление
                    $method = 'sendMessage';
                    $send_data = [
                        'chat_id' => $chat_id_moderator,
                        'text' => "username: ".$username
                        ."\nfirst_name: ".$first_name
                        ."\nlast_name: ".$last_name
                        ."\nis_bot: ".$is_bot
                        ."\nlanguage_code: ".$language_code,
                        'reply_markup' => json_encode(array('inline_keyboard' => 
                            [
                                [
                                    ['text'=>'Reject','callback_data'=>'{"action":"reject", "chat_id":"'.$chat_id.'", "market_id":"'.$row_adv['id'].'"}'],
                                    ['text'=>'Publish','callback_data'=>'{"action":"publish", "chat_id":"'.$chat_id.'", "market_id":"'.$row_adv['id'].'"}']
                                ]
                            ]
                        
                        )),
                        'disable_notification' => true
                    ];
                    
                    
                    $telegramApi->sendRequest($method, $send_data);
                    
                    exit(0);
                    
                } else {
                    $send_data = [
                        'text' => "Ошибка!\nНажмите Отмена и начните все сначала."
                    ];
                }
                
            } else {
                $send_data = [
                    'text' => "Ошибка добавления Контактов!\nДавайте попробуем еще."
                ];
            }
        }
        }
        
}


// Сохраняем в нашей базе данных что прислал пользователь и ему ответ text
// Дополняем наш массив, нашим ответов пользователю
if(array_key_exists("text", $send_data)) $userData['answer'] = $send_data['text'];
else  $userData['answer'] = 'media';

// Есл сложный ответ и нам надо его поймать, то
if($complicated_answer != '' ) $userData['answer'] = $complicated_answer;

// зменить имя ключа в массиве Do not use field names ending with _id, these are reserved for bean relations.
$userData['messageid'] = $userData['message_id'];
unset($userData['message_id']);

insertUserData($userData);

// Добавляем данные пользователя 
$send_data['chat_id']       = $chat_id;

// Отправляем ответ пользователю
$telegramApi->sendRequest($method, $send_data, $userData['first_name']);



// Подготавливаем к публикации само объявление
function send_advertising()
{
    global $row_adv, $send_data, $method;
    
    $method = 'sendMediaGroup';
    
    // Проверяем наличие фотографий в базе данных
    if($row_adv['photo1']) $send_data['media'][] = 
    [
        'type' => 'photo',
        'media' => $row_adv['photo1'],
        'caption' => $row_adv['header']."\n\nТематика: #".$row_adv['topic']."\nСтоимость: ".$row_adv['price']."\nСтрана: ".$row_adv['country']."\nГород: ".$row_adv['town']."\n".$row_adv['delivery']."\n\nОписание: ".$row_adv['contents']."\n\n".$row_adv['contacts'],
        'caption_entities' => 
        [
            [   // Заголовок и Тематика
                'offset' => 0,
                'length' => mb_strlen($row_adv['header']."Тематика:", 'utf8')+2,
                'type' => 'bold',
            ],
            [   // Стоимость
                'offset' => mb_strlen($row_adv['header']."Тематика: #".$row_adv['topic'], 'utf8')+3,
                'length' => mb_strlen("Стоимость:", 'utf8'),
                'type' => 'bold',
            ],
            [   // Страна
                'offset' => mb_strlen($row_adv['header']."Тематика: #".$row_adv['topic']."Стоимость: ".$row_adv['price'], 'utf8')+4,
                'length' => mb_strlen("Страна:", 'utf8'),
                'type' => 'bold',
            ],
            [   // Город
                'offset' => mb_strlen($row_adv['header']."Тематика: #".$row_adv['topic']."Стоимость: ".$row_adv['price']."Страна: ".$row_adv['country'], 'utf8')+5,
                'length' => mb_strlen("Город:", 'utf8'),
                'type' => 'bold',
            ],
            [   // Описание
                'offset' => mb_strlen($row_adv['header']."Тематика: #".$row_adv['topic']."Стоимость: ".$row_adv['price']."Страна: ".$row_adv['country'].",Город: ".$row_adv['town']."".$row_adv['delivery'], 'utf8')+7,
                'length' => mb_strlen("Описание:", 'utf8'),
                'type' => 'bold',
            ]
            
        ]
    ];
    if($row_adv['photo2']) $send_data['media'][] = 
    [
        'type' => 'photo',
        'media' => $row_adv['photo2']
    ];
    if($row_adv['photo3']) $send_data['media'][] = 
    [
        'type' => 'photo',
        'media' => $row_adv['photo3']
    ];  
}

// Отсылаем пользователю, что сообщение отправлено
function send_ok()
{
    global $userData, $telegramApi;

    
    $method = 'sendMessage';
    $send_data = [
        'chat_id' => $userData['chat_id'],
        'text' => "🤝 Ваше сообщение отослано",
        'reply_markup' => [
            'resize_keyboard' => true,
            'keyboard' => [
                [
                    ['text' => 'Отмена']
                ]
            ],
        ]
    ];
    
    // Есл сложный ответ и нам надо его поймать, то
    if($complicated_answer != '' ) $userData['answer'] = $complicated_answer;
    
    // зменить имя ключа в массиве Do not use field names ending with _id, these are reserved for bean relations.
    $userData['messageid'] = $userData['message_id'];
    unset($userData['message_id']);
    
    insertUserData($userData);
    
    // Отправляем ответ пользователю
    $telegramApi->sendRequest($method, $send_data, $userData['first_name']);

}


// Отсылаем пользователю, что сообщение отправлено
function send_false()
{
    global $userData, $telegramApi;

    $method = 'sendMessage';
    $send_data = [
        'chat_id' => $userData['chat_id'],
        'text' => "⁉️ Произошла ошибка. Ваше сообщение не отослано! :(",
        'reply_markup' => [
            'resize_keyboard' => true,
            'keyboard' => [
                [
                    ['text' => 'Отмена']
                ]
            ],
        ]
    ];
    
    // Есл сложный ответ и нам надо его поймать, то
    if($complicated_answer != '' ) $userData['answer'] = $complicated_answer;
    
    // зменить имя ключа в массиве Do not use field names ending with _id, these are reserved for bean relations.
    $userData['messageid'] = $userData['message_id'];
    unset($userData['message_id']);
    
    insertUserData($userData);
    
   
    // Отправляем ответ пользователю
    $telegramApi->sendRequest($method, $send_data, $userData['first_name']);
}

