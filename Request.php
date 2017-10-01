<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 30.09.2017
 * Time: 15:08
 */

// declare(strict_types=1);


namespace cheremhovo\smsTraffic;


use yii\base\Component;
use yii\base\InvalidConfigException;
use yii\httpclient\Client;

/**
 * http://www.smstraffic.ru/
 * Class Request
 * @package cheremhovo\smsTraffic
 */
class Request extends Component
{
    /**
     *
     */
    const EVENT_BEFORE_SEND = 'beforeSend';
    /**
     *
     */
    const EVENT_AFTER_SEND = 'afterSend';
    /**
     * @var string
     */
    public $url = 'https://api.smstraffic.ru/multi.php';

    /**
     * @var string
     */
    public $login = '';

    /**
     * @var string
     */
    public $password = '';

    /**
     * @var array
     */
    public $phones = [];

    /**
     * @var string
     */
    public $message = '';

    /**
     * Сообщение передано в кодировке UTF-8. Максимальное количество символов в одном СМС сообщении —
     * 70 (67 для склеенного сообщения). Если сообщение состоит исключительно из латинских символов, максимальное количество
     * символов не изменяется.
     * @var int
     */
    public $rus = 5;

    /**
     *
     * @var string
     */
    public $originator = '';

    /**
     * SMS отправляется как обычное сообщение. Значение по умолчанию — 0.
     * @var int
     */
    public $flash = 0;

    /**
     * Максимальное количество частей, на которые будет при необходимости разбит текст сообщения. Если текст сообщения
     * не укладывается в одну часть, то длина одной части сообщения ограничивается 153 символами для латиницы и 67
     * длякириллицы. Если сообщение после разбивки превышает установленное значение max_parts, то
     * отправлены будут первые max_parts частей, а остальные отброшены. По умолчанию установлено максимальное значение — 255.
     * @var int
     */
    public $max_parts = 255;


    /**
     * Интервал в секундах между рассылаемыми сообщениями. Например: 1, 0.5, 0.05. C помощью параметра gap вы можете
     * ускорять или замедлять рассылку при единовременной отправке нескольких сообщений. Следует отметить, что каждая
     * отдельная часть длинного сообщения считается отдельным СМС сообщением. Значение по умолчанию — 1. Минимально
     * возможное значение параметра — 0.05.
     * @var int
     */
    public $gap = 1;


    /**
     * Если необходимо отправить индивидуальное сообщение каждому абоненту, можно либо несколько раз запрашивать
     * скрипт, передавая в качестве параметра phones только один телефон, либо (что более предпочтительно) передать
     * дополнительный параметр individual_messages=1, поле message оставить пустым, а в поле phones передать список
     * телефонов и сообщений в формате: телефон1 сообщение1 телефон2 сообщение2 телефон3 сообщение3 (телефон и
     * сообщение разделяются одним пробелом, пары телефон-сообщение разделяются знаком перевода строки (символ с ASCII
     * кодом 0xA или 0xD), текст сообщения не может содержать символа перевода строки). Значение по умолчанию — 0
     * @var int
     */
    public $individual_messages = 0;

    /**
     * Если необходимо получить информацию об идентификаторах, присвоенных каждому сообщению (они понадобятся при
     * проверке статуса доставки сообщения), нужно передать параметр want_sms_ids=1. Тогда ответный XML будет содержать
     * информацию о каждом телефоне и идентификаторе, присвоенном соответствующему сообщению. Параметр want_sms_ids=1
     * нельзя использовать в отсроченной рассылке, то есть одновременно с параметром start_date. Этот параметр можно
     * применять только при условии, что сообщение отправится не позднее 5 минут с момента поступления запроса.
     * Например, указав параметр gap=1, а в параметре phones — 301 номер телефона, мы получим, что 301-е сообщение
     * должно уйти через 301 секунду, то есть более чем через 5 минут. В этом случае вернется ошибка 418 и ни одно из
     * сообщений не будет отправлено. Идентификатор представлен целым беззнаковым числом размером 8 байт.
     * Максимальное число сообщений в одном запросе с использованием данного параметра не должно превышать 6000 при
     * указании минимального gap. Значение по умолчанию — 0.
     * @var int
     */
    public $want_sms_ids = 0;


    /**
     * Параметр используется совместно с параметрами want_sms_ids=1 и individual_messages=1. Параметр используется,
     * если необходимо передать каждое сообщение со своим уникальным идентификатором и при этом в ответе получить
     * привязку переданных идентификаторов к выданным нашей системе идентификаторам. Как правило, используется при
     * передаче длинных сообщений. В обычных условиях, при отправке длинного сообщения оно разбивается на несколько
     * частей и каждой части присваивается свой идентификатор. Чтобы связать несколько идентификаторов одного сообщения
     * применяется данный параметр. При этом в параметре phones перед каждым номером должен буть указан произвольный
     * идентификатор (это может быть идентификатор сообщения в вашей базе) отделённый от номера двоеточием.
     * Идентификатор не должен содержать в себе двоеточие. Пример: push_id1:телефон1 сообщение1 из двух частей
     * 12345678:телефон2 сообщение2 из двух частей one-more:телефон3 сообщение3 из двух частей Значение по умолчанию — 0.
     * @var int
     */
    public $with_push_id = 0;


    /**
     * Данный параметр используется при единовременной рассылке на несколько номеров телефона. В случае если у вас
     * нет уверенности что каждый из передаваемых в запросе номеров является корректным номером телефона, то при
     * обычных обстоятельствах возвращается ошибка 418 и ни одно из сообщений не отправляется. Если же установить этот
     * параметр ignore_phone_format=1, то проверка на номер телефона отключается и все сообщения, независимо от
     * корректности номера, становятся в очередь на отправку и биллингуются соответственно вашему тарифу. Значение по
     * умолчанию — 0.
     * @var int
     */
    public $ignore_phone_format = 0;

    /**
     * Параметр позволяет указать способ UDH-склейки. Если указано 1 — используется склейка с reference number
     * размером 2 байта. В противном случае используется склейка с reference number размером 1 байт.
     * 2-х байтовый reference number позволяет значительтно снизить вероятность некорректной склейки сообщений в
     * телефоне, однако уменьшает максимальный размер одной части на 1 символ. То есть не более 152 символов
     * латиницей и 66 символов кириллицей. О том, что такое UDH и в чём различие между склейками см. в разделе
     * UDH-склейка. Значение по умолчанию — 0.
     * @var int
     */
    public $two_byte_concat = 0;
    /**
     * @var Client
     */
    private $client;


    /**
     * Request constructor.
     * @param Client $client
     * @param array $config
     */
    public function __construct(
        Client $client,
        array $config = []
    )
    {
        parent::__construct($config);
        $this->client = $client;
    }


    /**
     * @throws InvalidConfigException
     */
    public function init()
    {
        parent::init();
        if (empty($this->login)) {
            throw new InvalidConfigException(static::className() . '::login must be set');
        }
        if (empty($this->password)) {
            throw new InvalidConfigException(static::className() . '::password must be set');
        }
        if (empty($this->url)) {
            throw new InvalidConfigException(static::className() . '::url must be set');
        }
        if (empty($this->originator)) {
            throw new InvalidConfigException(static::className() . '::originator must be set');
        }
    }


    /**
     * @return Response
     * @throws InvalidConfigException
     */
    public function send()
    {
        if (empty($this->message)) {
            throw new InvalidConfigException(static::className() . '::message must be set');
        }
        if (empty($this->phones)) {
            throw new InvalidConfigException(static::className() . '::phones must be set');
        }

        $options = [
            'login' => $this->login,
            'password' => $this->password,
            'phones' => implode(',', $this->phones),
            'message' => $this->message,
            'rus' => $this->rus,
            'originator' => $this->originator,
            'flash' => $this->flash,
            'max_parts' => $this->max_parts,
            'gap' => $this->gap,
            'individual_messages' => $this->individual_messages,
            'want_sms_ids' => $this->individual_messages,
            'with_push_id' => $this->with_push_id,
            'ignore_phone_format' => $this->individual_messages,
            'two_byte_concat' => $this->two_byte_concat,
        ];
        $this->trigger(static::EVENT_BEFORE_SEND);
        $response = $this->client->post($this->url, $options)->send();
        $this->trigger(static::EVENT_AFTER_SEND);
        return new Response($response);
    }
}