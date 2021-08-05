![ozon](https://github.com/MirAlexSky/ozon-logistics-api/blob/master/Ozon.png "Ozon")
# Ozon Logistics ([Ozon:rocket:Rocket](https://rocket.ozon.ru/))
[![Latest Stable Version](http://poser.pugx.org/miralexsky/ozon-logistics-api/v)](https://packagist.org/packages/miralexsky/ozon-logistics-api)
[![Total Downloads](http://poser.pugx.org/miralexsky/ozon-logistics-api/downloads)](https://packagist.org/packages/miralexsky/ozon-logistics-api)
[![License](http://poser.pugx.org/miralexsky/ozon-logistics-api/license)](https://packagist.org/packages/miralexsky/ozon-logistics-api)
```
composer require miralexsky/ozon-logistics-api
```


### :heavy_check_mark:Возможности библиотеки
- [X] Авторизация и получение токена (тестового или боевого)
- [X] Получение списка пунктов выдачи
- [X] Получение способов доставки (курьер, ПВЗ)
- [X] Получение списка тарифов (Цена доставки до пункта выдачи)
- [X] Создание заказов
- [X] Получение информации по заказам
- [X] Получение наклейки (тикет)
- [X] Трекинг заказов

> Работа с боевым API возможна только при наличии договора с Ozon

<h2>:rocket:Начало работы</h2>

```php

use Miralexsky\OzonApi\OzonClient;

$client = new OzonClient(false);

try {
  $token = $client->authorize(); 
} catch (OzonClientException $e) { 
  
} 

$cities = $client->getCities();
```
### :dart:В разработке
- [ ] Печать накладных
- [ ] Отмена заказов
