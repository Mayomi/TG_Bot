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

    public function execute(): ServerResponse
    {
        Request::deleteMessage([
            'chat_id'    => $this->getMessage()->getChat()->getId(),
            'message_id' => $this->getMessage()->getMessageId(),
        ]);
        $code = trim($this->getMessage()->getText(true));
        $username = $this->getMessage()->getFrom()->getUsername();
        if ($code === '') {
            return $this->replyToChat('*名称解析错误* ' . $this->getUsage(), [
                'parse_mode' => 'markdown',
            ]);
        }
        $text = '';
        $inline_keyboard = new InlineKeyboard([
            ['text' => 'Inline Query (current chat)', 'switch_inline_query_current_chat' => 'inline query...'],
            ['text' => 'Inline Query (other chat)', 'switch_inline_query' => 'inline query...'],
        ], [
            ['text' => 'Callback', 'callback_data' => 'identifier'],
            ['text' => 'Open URL', 'url' => 'https://github.com/php-telegram-bot/example-bot'],
        ]);

        return $this->replyToChat('Inline Keyboard', [
            'reply_markup' => $inline_keyboard,
        ]);
    }
}
