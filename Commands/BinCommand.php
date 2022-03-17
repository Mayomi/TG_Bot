<?php

namespace Longman\TelegramBot\Commands\UserCommands;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Exception\TelegramException;
use Longman\TelegramBot\Request;
use Longman\TelegramBot\TelegramLog;

class BinCommand extends UserCommand
{
    protected $name = 'bin';
    protected $description = '获取指定卡头的卡信息';
    protected $usage = '指令格式：/bin 6-8位卡头';
    protected $version = '1.0.0';
    private $api_base_url = 'https://lookup.binlist.net/';

    private function getData($bin): string
    {
        $client = new Client(['base_uri' => $this->api_base_url]);
        try {
            $response = $client->get($bin);
        } catch (RequestException $e) {
            TelegramLog::error($e->getMessage());
            return '';
        }
        return (string) $response->getBody();
    }
    private function getString(array $data, string $bin): string
    {
        try {
            return sprintf(
                '*卡头: %s' . PHP_EOL .
                    '种类: %s' . PHP_EOL .
                    '级别: %s' . PHP_EOL .
                    '银行: %s' . PHP_EOL .
                    '国家: %s%s' . PHP_EOL .
                    '类型: %s*',
                $bin,
                $data['scheme'],
                $data['brand'],
                $data['bank'],
                $data['country']['name'],
                $data['country']['emoji'],
                $data['type']
            );
        } catch (TelegramException $e) {
            TelegramLog::error($e->getMessage());

            return '*卡头解析错误*';
        }
    }
    public function execute(): ServerResponse
    {
        Request::deleteMessage([
            'chat_id'    => $this->getMessage()->getChat()->getId(),
            'message_id' => $this->getMessage()->getMessageId(),
        ]);
        $bin = trim($this->getMessage()->getText(true));
        $username = $this->getMessage()->getFrom()->getUsername();
        if ($bin === '') {
            return $this->replyToChat('*卡头解析错误* ' . $this->getUsage(), [
                'parse_mode' => 'markdown',
            ]);
        }
        if ($bin_data = json_decode($this->getData($bin), true)) {
            $text = $this->getString($bin_data, $bin);
        }
        return $this->replyToChat($text . '
查询人 @' . $username, [
            'parse_mode' => 'markdown',
        ]);
    }
}
