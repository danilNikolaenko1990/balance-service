Сервис готов только на уровне тестов, остались инфраструктурные дела

что бы не заморачиваться с установкой postgres на хост систему,
 я использовал контейнер (все настройки дефолтные) 
```
docker run --name some-postgres -e POSTGRES_PASSWORD=mysecretpassword -p 5432:5432 -d postgres
```
в папке app/config файл parameters.yml должен быть такой же, как parameters.yml.dist

во время установки enqueue/enqueue-bundle 
enqueue/amqp-ext столкнулся с проблемой отсутствия расширения amqp,
в итоге делал так:
```
sudo apt-get -y install librabbitmq-dev
sudo pecl install amqp
```
(на всякий случай вот инструкция https://serverpilot.io/community/articles/how-to-install-the-php-amqp-extension.html)

не забыть добавить в php.ini extension=amqp.so

все это дело тестилось на php7.2, в моем случае нужно было еще поставить
* php7.2-bcmath
* php7.2-mbstring
* ext-xml 
* php-dev

для работы Unit тестов (тестирование репозиториев через sqlite) надо поставить php-sqlite3 
```
sudo apt install sqlite php-sqlite3
```
Далее
```
composer install
```
php bin/console doctrine:migrations:migrate

для запуска юнит тестов через phpstorm 
указываем в настройках use composer autoload
path to autoload -  autoload.php из папки vendor

Сам сервис на уровне тестов готов, по сути осталось реализовать userOperationLocker, 
я планировал просто делать insert ON CONFLICT с userId и потом select for update, по выбросу 
исключения просто реджектить очередь. Можно юзать что-нибудь более быстрое, redis, например.


Для работы с rabbit планировал юзать абстракцию 
enqueue/enqueue-bundle
для сериализации - JMS serializer (хоть он и медленный)

В итоге осталось - заюзать enqueue-bundle, в котором сделать вызов сервиса, и реализовать userOperationLocker, так же планирую обернуть таки это все в контейнер.