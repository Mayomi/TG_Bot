<?php

namespace Longman\TelegramBot\Commands\UserCommands;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Exception\TelegramException;
use Longman\TelegramBot\Request;
use Longman\TelegramBot\TelegramLog;

class RateCommand extends UserCommand
{
    protected $name = 'rate';
    protected $description = '获取指定数量的货币转换值';
    protected $usage = '指令格式：/rate 货币1 货币2 数量';
    protected $version = '1.0.0';

    private function getData(): string
    {
        $client = new Client(['base_uri' => $this->getConfig('dy_api_url')]);
        $api = "api_currency.json";
        try {
            $response = $client->get($api);
        } catch (RequestException $e) {
            TelegramLog::error($e->getMessage());
            return '';
        }
        return (string) $response->getBody();
    }
    private function getCountry(array $data, string $country): string
    {
        foreach ($data["rates"] as $key => $value) {
            if ($country == $key) {
                return true;
            }
        }
        return false;
    }
    private function getString(array $data, string $from_Currency, string $to_Currency, $amount): string
    {
        $val = $data["rates"]["$to_Currency"] / $data["rates"]["$from_Currency"];
        $total = round($val * $amount, 4);
        if (!(isset($data['success'])) || $data['success'] == false) {
            return '*汇率解析错误*';
        }

        try {
            return sprintf(
                '***%s : %s = %s : %s ***',
                $from_Currency,
                $to_Currency,
                $amount,
                $total
            );
        } catch (TelegramException $e) {
            TelegramLog::error($e->getMessage());

            return '';
        }
    }
    public function execute(): ServerResponse
    {
        Request::deleteMessage([
            'chat_id'    => $this->getMessage()->getChat()->getId(),
            'message_id' => $this->getMessage()->getMessageId(),
        ]);
        $from_Currency = strtoupper(substr($this->getMessage()->getText(true), 0, 3));
        $to_Currency = strtoupper(substr($this->getMessage()->getText(true), 4, 3));
        $username = $this->getMessage()->getFrom()->getUsername();
        if ($from_Currency === '' or $to_Currency === '') {
            return $this->replyToChat('*汇率解析错误* ' . $this->getUsage(), [
                'parse_mode' => 'markdown',
            ]);
        }
        $amount = substr($this->getMessage()->getText(true), 8);
        if (empty($amount) == false) {
            $amount = floatval($amount);
        } else {
            $amount = 100;
        }
        $data = json_decode($this->getData(), true);
        $form_result = $this->getCountry($data, $from_Currency);
        $to_result = $this->getCountry($data, $to_Currency);
        if ($form_result and $to_result) {
            $text = $this->getString($data, $from_Currency, $to_Currency, $amount);
        } else {
            return $this->replyToChat('*汇率解析错误* '.$this->getUsage(), [
                'parse_mode' => 'markdown',
            ]);
        }
        return $this->replyToChat($text . '
查询人 @' . $username, [
            'parse_mode' => 'markdown',
        ]);
    }
}
