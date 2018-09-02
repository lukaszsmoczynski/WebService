<pre>
  <?php
  //request url
  $url = 'https://fcm.googleapis.com/fcm/send';

  //your api key
  $apiKey = 'AAAAD1PYLdQ:APA91bGpZ_7CBIalsPZbV7ldylknoUP44UKifn-9BECeDTJ6H-PRJcxuyvQQoLgiLxYTF6BZ_IU9SJBEC6BLjs1uWs_Kur8L_NqMcDakNvAW_m_7hHPvVoBg-1R4JojmsP6oj0HvoIwz';

  //payload data
  $data = array('score' => '1-0', 'scorer' => 'Ronaldo', 'time' => '44');
  $to = '/topics/news';

  $fields = array('to' => $to,
      'data' => $data);

  //http header
  $headers = array('Authorization: key=' . $apiKey,
      'Content-Type: application/json');

  //curl connection
  $ch = curl_init();

  curl_setopt($ch, CURLOPT_URL, $url);
  curl_setopt($ch, CURLOPT_POST, true);
  curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
  curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));

  $result = curl_exec($ch);

  curl_close($ch);

  echo $result;
  ?>
</pre>