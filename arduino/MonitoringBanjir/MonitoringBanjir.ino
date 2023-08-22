#include <ESP8266WebServer.h>
#include <ESP8266HTTPClient.h>
#include <WiFiClient.h>
#include <ArduinoJson.h>

#include <NTPClient.h>
#include <WiFiUdp.h>

#include <Servo.h>

//wifi
const char* ssid = "Kedai Kopi ARKHA";
const char* password = "sukasuka";

String serverUrl = "http://192.168.1.22";
String token_sensor = "64c52b96bf31f_1690643350";

WiFiUDP ntpUDP;
NTPClient timeClient(ntpUDP, "pool.ntp.org");

//daya tampung array
DynamicJsonDocument tampungdata(500);
DynamicJsonDocument postData(500);
DynamicJsonDocument tanggapanPostData(500);

//servo
const int pinServo = D4;
Servo myServo;
int nilaiPutar = 90;
int tinggi = 0;
int tinggiMax = 4;

//watersensorpin
const int waterSensorPin = A0;

//buzzer dan led
int notif = 0;
const int ledPinD1 = D1;  // Pin LED untuk nilai 1
const int ledPinD2 = D2;  // Pin LED untuk nilai 2
const int ledPinD3 = D3;  // Pin LED untuk nilai 3
#define BUZZER_PIN D0


void setup() {
  Serial.begin(115200);
  WiFi.begin(ssid, password);

  Serial.print("Connecting");
  while (WiFi.status() != WL_CONNECTED) {
    Serial.print(".");
    delay(500);
  }

  Serial.print("Successfully connected to : ");
  Serial.println(ssid);
  Serial.print("IP address: ");
  Serial.println(WiFi.localIP());

  timeClient.begin();
  timeClient.setTimeOffset(0);
  
  myServo.attach(pinServo);
  stopServo();
  pinMode(ledPinD1, OUTPUT);
  pinMode(ledPinD2, OUTPUT);
  pinMode(ledPinD3, OUTPUT);
  pinMode(BUZZER_PIN, OUTPUT);
  
  dataServer();

}

void loop() {
 
 gerak();

 delay(2000);
}





void dataServer() {
   HTTPClient http;
   WiFiClient client;
   String url = "/banjir/public/api/alat/data";
   url = serverUrl + url;
   client.connect(serverUrl, 80);
   http.begin(client, url);

   int httpResponseCode = http.GET();

   if (httpResponseCode == HTTP_CODE_OK) {
      String response = http.getString();
  
      deserializeJson(tampungdata, response);
      Serial.println(response);
      
      JsonObject obj = tampungdata.as<JsonObject>();
      tinggi = obj["tinggi"];
      tinggiMax = obj["tinggiMax"];
      
   }else {
      Serial.print("Gagal mengambil data. Kode respons: ");
      Serial.println(http.errorToString(httpResponseCode));
   }
}




void gerak(){
    int nilai = waterSensor();

    if(nilai>= 63) {
      if(tinggi<tinggiMax){
        naik();
      }
    }else if(nilai > 25){
      if(tinggi>0){
        turun();
      }
    }else if (nilai <= 10) {
      if(tinggi>0){
        turun();
      }
    }

    upload();
}

void upload() {
  timeClient.update();
  time_t epochTime = timeClient.getEpochTime();
  String kirim = "";

  JsonObject datasensor = postData.to<JsonObject>();
  datasensor["tinggi"] = tinggi;
  datasensor["waktu"] = epochTime;

  HTTPClient http;
  WiFiClient client;
  String url = "/banjir/public/api/alat/sensor/post";
  url = serverUrl + url;

  serializeJson(datasensor, kirim);

  Serial.println(kirim);

  client.connect(serverUrl, 80);
  http.begin(client, url);

  http.addHeader("Content-Type", "application/json");
  http.addHeader("token-sensor", String(token_sensor));
  int httpResponseCode = http.POST(kirim);

  if (httpResponseCode == HTTP_CODE_OK) {
    String response = http.getString();
    deserializeJson(tanggapanPostData, response);

    JsonObject obj = tanggapanPostData.as<JsonObject>();
    int buzzer = obj["buzzer"].as<int>();
    int led = obj["led"].as<int>();
  
    if(notif != buzzer) {
      notif = buzzer;
      notifikasi(notif);
    }
    notifikasiLED(led);
    
  }else {
    Serial.print("Gagal mengambil data upload");
  }

  tanggapanPostData.clear();
  postData.clear();
  
}



int waterSensor() {
  int rawValue = analogRead(waterSensorPin);  // Baca nilai analog dari sensor air
  int waterValue = map(rawValue, 0, 1023, 0, 100);  // Mapping nilai analog menjadi persentase
  Serial.println(String(waterValue)+"Cm");
  return waterValue;  // Kembalikan nilai persentase kelembaban
}


void stopServo() {
  myServo.write(90); 
}

void naik() {
    myServo.write(0); 
    delay(240);
    tinggi = tinggi + 1;
    stopServo();
}

void turun() {
    myServo.write(180);
    delay(251.5);
    tinggi = tinggi - 1;
    stopServo();
}


void notifikasi(int jumlahNotifikasi) {
  for (int i = 0; i < jumlahNotifikasi; i++) {
    digitalWrite(BUZZER_PIN, HIGH);
    if(jumlahNotifikasi == 2){
        delay(2000); 
    }else if(jumlahNotifikasi == 1){
        delay(4000); 
    }
    
    digitalWrite(BUZZER_PIN, LOW);
    if(jumlahNotifikasi == 2){
        delay(1000); 
    }
  }
}

void notifikasiLED(int nilai) {
  if (nilai == 1) {
      digitalWrite(ledPinD1, HIGH);
      digitalWrite(ledPinD2, LOW);
      digitalWrite(ledPinD3, LOW);
      Serial.println("Hijau");
  } else if (nilai == 2) {
      digitalWrite(ledPinD2, HIGH);
      digitalWrite(ledPinD1, LOW);
      digitalWrite(ledPinD3, LOW);
      Serial.println("Kuning");
  } else if (nilai == 3) {
      digitalWrite(ledPinD3, HIGH);
      digitalWrite(ledPinD2, LOW);
      digitalWrite(ledPinD1, LOW);
      Serial.println("Merah");
  }
}
