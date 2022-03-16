<?php

namespace Longman\TelegramBot\Commands\UserCommands;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Exception\TelegramException;
use Longman\TelegramBot\Request;
use Longman\TelegramBot\TelegramLog;

class NFCommand extends UserCommand
{
    protected $name = 'nf';
    protected $description = '获取指定国家或地区代码的Netflix价格表';
    protected $usage = '指令格式：/nf 国家或地区代码';
    protected $version = '1.0.0';

    private function getCountry($code): string
    {
        $client = new Client(['base_uri' => $this->getConfig('dy_api_url')]);
        $api = "getCountry.php";
        $query  = [
            'code'     => $code,
        ];
        try {
            $response = $client->get($api, ['query' => $query]);
        } catch (RequestException $e) {
            TelegramLog::error($e->getMessage());
            return '';
        }
        return (string) $response->getBody();
    }
    private function getNetflix($country): string
    {
        $client = new Client(['base_uri' => $this->getConfig('dy_api_url')]);
        $api = "getNetflix.php";
        $query  = [
            'code'     => $country,
        ];
        try {
            $response = $client->get($api, ['query' => $query]);
        } catch (RequestException $e) {
            TelegramLog::error($e->getMessage());
            return '';
        }
        return (string) $response->getBody();
    }
    private function getRates(): string
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
    private function getString(array $data): string
    {
        $Country = $data["Country"];
        $Currency = $data["Currency"];
        $Basic = str_replace(",", "", $data["Basic"]);
        $Standard = str_replace(",", "", $data["Standard"]);
        $Premium = str_replace(",", "", $data["Premium"]);

        $rate = json_decode($this->getRates(), true);
        $val = $rate["rates"]["CNY"] / $rate["rates"]["$Currency"];
        $Basic_CNY = round($val * $Basic, 2);
        $Standard_CNY = round($val * $Standard, 2);
        $Premium_CNY = round($val * $Premium, 2);

        try {
            return sprintf(
                '`国家或地区名：%s' . PHP_EOL .
                    '使用货币：%s' . PHP_EOL .
                    '基本套餐：%s / %s 元' . PHP_EOL .
                    '标准套餐：%s / %s 元' . PHP_EOL .
                    '高级套餐：%s / %s 元`',
                $Country,
                $Currency,
                $Basic,
                $Basic_CNY,
                $Standard,
                $Standard_CNY,
                $Premium,
                $Premium_CNY
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
        $code = trim($this->getMessage()->getText(true));
        $code = strtoupper($code);
        $username = $this->getMessage()->getFrom()->getUsername();
        if ($code === '') {
            return $this->replyToChat('`代码解析错误` ' . $this->getUsage(), [
                'parse_mode' => 'markdown',
            ]);
        }
        $country_data = json_decode($this->getCountry($code), true);
        $text = "";
        if ($country_data["result"]) {
            $netflix_data = json_decode($this->getNetflix($country_data["code"]), true);
            if ($netflix_data["result"] and isset($netflix_data["Currency"]) and isset($netflix_data["Premium"])) {
                $text = $this->getString($netflix_data);
            } else {
                return $this->replyToChat('`代码解析错误` ' . $this->getUsage(), [
                    'parse_mode' => 'markdown',
                ]);
            }
        } else {
            return $this->replyToChat('`代码解析错误` ' . $this->getUsage(), [
                'parse_mode' => 'markdown',
            ]);
        }
        return $this->replyToChat($text . '
查询人 @' . $username, [
            'parse_mode' => 'markdown',
        ]);
    }
}
