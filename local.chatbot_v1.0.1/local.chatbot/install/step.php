<?php
if (!check_bitrix_sessid()) return;
echo CAdminMessage::ShowNote("Модуль local.chatbot установлен. Провайдер событий активирован.");