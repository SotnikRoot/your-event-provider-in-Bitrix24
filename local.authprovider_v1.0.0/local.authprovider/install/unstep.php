<?php
if (!check_bitrix_sessid()) return;
echo CAdminMessage::ShowNote("Модуль local.authprovider успешно удален.");