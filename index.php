<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Запрос на efind</title>
</head>
<body>
    <form method="post">
    <label>Введите запрос:</label>
    <input id="inpRequest" name="request" type="text"/>
    <button type="submit">Запрос</button>
	</form>
<?php  
define("API_URL", "https://efind.ru/api/search/");
define("API_TOKEN", "83fa156e-e675-4781-be0a-bb0913ee869d");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $url = makeUrl(API_URL, API_TOKEN, $_POST['request']);
    $code = 0;
    $out = curlGet($url, $code);
    if ($code == 200) {
	    $data = json_decode($out, TRUE); # Приводим отклик сервера к ассоциативному массиву
	    if (isset($data['error'])){
	        # Запрос отработал с ошибкой.
	        echo $data['error'];
	    } else {
	    	$prices = [];
	    	$parsedData = calculateResult($data, $prices);
			printPrices($prices);
			printPartTable($parsedData);
	    }
	} else {
		echo "Ошибочный ответ сервера. Код ответа $code";
	}
}

/**
 * Парсинг ответа от апи сервиса
 * @param $data - массив ответов от апи
 * @param $prices - массив для записей цен
 * @return - массив объектов с полями(title, part, instock, delivery), также возвращаются ценны в саммиве $prices
 */
function calculateResult($data, &$prices) {
	$result = [];
	foreach ($data as $stockrow) {
		$title = $stockrow['stockdata']['title'];
		foreach ($stockrow['rows'] as $row) {
			//сюда возможно надо сделать пересчет валют по актуальному курсу
			foreach ($row['price'] as $price) {
				if($price[2]) {
					$prices[] = floatval($price[2]);
				}
			}
			$result[] = [
				'title' => $title,
				'part' => $row['part'],
				'instock' => $row['instock'] ? "на складе" : "нет на складе",
				'delivery' => $row['dlv']
			];
		}
	}
	return $result;
}

/**
 * Вывод таблицы с данными по поиску
 * @param $data - массив объектов с полями(title, part, instock, delivery) для вывода
 */
function printPartTable($data) {
	echo "<table><tr><th>Поставщик</th><th>Партномер</th><th>Наличие</th><th>Срок поставки</th>";
	$fields = ['title', 'part', 'instock', 'delivery'];
	foreach ($data as $row) {
		echo "<tr>";
			foreach ($fields as $field) {
				echo "<td>" . $row[$field] . "</td>";
			}
		echo "</tr>";
	}
	echo "</table>";
}

/**
 * Вывод цен (мин, макс, средняя, медиана)
 * @param $prices - массив цен
 */
function printPrices($prices) {
	sort($prices);
	$cprices = count($prices);
	echo "<br/>Минимальная цена: ". $prices[0];
	echo "<br/>Средняя цена: " . array_sum($prices) / $cprices;
	echo "<br/>Медианная цена: " . $prices[$cprices/2];
	echo "<br/>Максимальная цена: " . $prices[$cprices-1];
}

/**
 * Сосавить урл
 * @param $apiUrl - путь к api
 * @param $token - токен доступа
 * @param $request - строка поиска
 * @return - url для запроса
 */
function makeUrl($apiUrl, $token, $request) {
	return $apiUrl . urlencode ( $request ) . "?access_token=" . $token;
}

/**
 * Выполнить get запрос
 * @param $url - путь запроса
 * @param $code - код возврата запроса
 * @return - данные запроса и код возврата в $code
 */
function curlGet($url, &$code) {
	$curl=curl_init(); #Сохраняем дескриптор сеанса cURL
	#Устанавливаем необходимые опции для сеанса cURL
	curl_setopt($curl,CURLOPT_RETURNTRANSFER,true);
	curl_setopt($curl,CURLOPT_USERAGENT,'eFind-API-client/1.0');
	curl_setopt($curl,CURLOPT_URL,$url);
	curl_setopt($curl,CURLOPT_CUSTOMREQUEST,'GET');
	curl_setopt($curl,CURLOPT_HTTPHEADER,array('Content-Type: application/json'));
	curl_setopt($curl,CURLOPT_HEADER,false);

	$out=curl_exec($curl); #Инициируем запрос к API и сохраняем ответ в переменную

	$code=(int)curl_getinfo($curl,CURLINFO_HTTP_CODE); #Получим HTTP-код ответа сервера

	curl_close($curl); #Завершаем сеанс cURL
	return $out;
}
?>
</body>
</html>    
