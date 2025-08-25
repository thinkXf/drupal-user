#!/bin/bash
# drupal_cron_advanced.sh

echo "Drupalのcron実行..."

CRON_KEY="SYxE7MCgs9Lb4rxqpSGckMLvpCO58NBc920s9GBJdmoIEafB_LMonbyqzEkCWaRj10Do3Oy5dQ"


curl -fsS "https://drupal-user.ddev.site/cron/${CRON_KEY}"

if [ $? != 0 ]; then
    echo "Drupalのcron実行に失敗しました。"
    exit 1
else
    echo "Drupalのcron実行に成功しました"
    exit 0
fi