<?php

namespace Longman\TelegramBot\Commands\UserCommands;

use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Entities\InlineKeyboard;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Request;

class CardCommand extends UserCommand
{
    protected $name = 'card';
    protected $description = '获取指定土耳其礼品卡的购买方式';
    protected $usage = '指令格式：/card nf/ap/gp';
    protected $version = '1.0.0';

    private function getKeyboard($name): string
    {   
        switch ($name) {
            case 'Netflix':
                $inline_keyboard = new InlineKeyboard([
                    ['text' => 'TurGame', 'url' => 'https://www.turgame.com/netflix-gift-card/'],
                    ['text' => 'MTCGame', 'url' => 'https://www.mtcgame.com/en-UG/netflix/netflix-hediye-karti'],
                    ['text' => 'trendyol', 'url' => 'https://www.trendyol.com/sr?q=netfl%C4%B1x'],
                ]);
                break;
            case 'Apple':
                $inline_keyboard = new InlineKeyboard([
                    ['text' => 'TurGame', 'url' => 'https://www.turgame.com/app-store-itunes-gift-card/'],
                    ['text' => 'MTCGame', 'url' => 'https://www.mtcgame.com/en-UG/apple-store/itunes-hediye-karti'],
                    ['text' => 'Epin', 'url' => 'https://www.epin.com.tr/appstore-itunes-bakiye'],
                ]);
                break;
            case 'Google Play':
                $inline_keyboard = new InlineKeyboard([
                    ['text' => 'TurGame', 'url' => 'https://www.turgame.com/google-play-gift-card/'],
                    ['text' => 'MTCGame', 'url' => 'https://www.mtcgame.com/en-UG/google-play/google-play-bakiye-kodlari'],
                    ['text' => 'Epin', 'url' => 'https://www.epin.com.tr/google-play-bakiyesi'],
                ]);
                break;
        }
        return $inline_keyboard;
    }
    public function execute(): ServerResponse
    {
        Request::deleteMessage([
            'chat_id'    => $this->getMessage()->getChat()->getId(),
            'message_id' => $this->getMessage()->getMessageId(),
        ]);
        $code = trim($this->getMessage()->getText(true));
        $code = strtolower($code);
        $username = $this->getMessage()->getFrom()->getUsername();
        if ($code === '') {
            return $this->replyToChat('`名称解析错误` ' . $this->getUsage(), [
                'parse_mode' => 'markdown',
            ]);
        }
        switch ($code) {
            case 'nf':
                $name = 'Netflix';
                break;
            case 'ap':
                $name = 'Apple';
                break;
            case 'gp':
                $name = 'Google Play';
                break;
            default:
                $name = '';
        }
        if ($name === '') {
            return $this->replyToChat('`名称解析错误` ' . $this->getUsage(), [
                'parse_mode' => 'markdown',
            ]);
        }

        return $this->replyToChat('`帮你找到了这些购买' . $name . '礼品卡的方法：`
查询人 @' . $username, [
            'reply_markup' => $this->getKeyboard($name),
            'parse_mode' => 'markdown',
        ]);
    }
}
