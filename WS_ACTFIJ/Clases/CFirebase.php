<?php
require_once "Clases/CBase.php";
class CFirebase extends CBase {
   public function send($p_title, $p_body, $p_ids) {
      $title = "";
      $body =
         "Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Commodo quis imperdiet massa tincidunt nunc pulvinar sapien. Tortor condimentum lacinia quis vel eros donec ac. Facilisi cras fermentum odio eu feugiat pretium nibh ipsum. Morbi quis commodo odio aenean. Donec massa sapien faucibus et molestie ac feugiat sed. Consequat ac felis donec et odio pellentesque diam volutpat commodo. Ipsum suspendisse ultrices gravida dictum fusce ut placerat orci. Phasellus egestas tellus rutrum tellus pellentesque eu tincidunt. Lacus vel facilisis volutpat est velit egestas dui id ornare. Purus sit amet luctus venenatis lectus magna fringilla. Vivamus at augue eget arcu dictum varius duis at consectetur. Sagittis nisl rhoncus mattis rhoncus urna neque viverra justo. Molestie a iaculis at erat pellentesque adipiscing commodo elit. Nisl rhoncus mattis rhoncus urna neque viverra. Cursus vitae congue mauris rhoncus aenean. Felis bibendumss.";
      $fields = [
         "registration_ids" => [
            "caZr6DlnNyL9IOlU9is9rj:APA91bFDYRILevwY5gIu0l4ooNAqqXzOYAlNfHpfAWPRYZ-eEJ2_ptd41rylvQDkIQGB7g0wN4ZS1_B_uTJxz7e9a8ZXEbnQzTnHmUyuO3S7e8aHhZp5MjnGPCoyNWzSQw21nrQGxpe3",
         ],
         "notification" => [
            "title" => "Do you find it...",
            "body" => $body,
            "click_action" => "/",
         ],
      ];
      return $this->sendPushNotification($fields);
   }
   private function sendPushNotification($fields) {
      //require_once 'Config.php';
      $url = "https://fcm.googleapis.com/fcm/send";
      $headers = [
         "Authorization: key=" .
         "AAAAFG7oIo:APA91bGu-A5rh9az-dXaYJzYcaMnAuMQe24WSgCknJz9EXE7wZs0lzvLoVq6qj8fY-Pgr9qVWn5NLkdX8m9w9msot99WVVN5jtilSiaJtM-KaH5teHLIuth4zL45b7XwOzQykd5IPCx",
         "Content-Type: application/json",
      ];
      $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL, $url);
      curl_setopt($ch, CURLOPT_POST, true);
      curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
      curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));

      curl_setopt($ch, CURLOPT_SSLVERSION, 6);

      $result = curl_exec($ch);
      if ($result === false) {
         die("Curl failed: " . curl_error($ch));
      }
      curl_close($ch);
      return $result;
   }
}
