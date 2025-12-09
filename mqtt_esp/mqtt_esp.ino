#include <WiFi.h>
#include <WiFiClientSecure.h>
#include <PubSubClient.h>
#include <Wire.h>
#include <ArduinoJson.h>

const char* ssid = "Galaxy"; 
const char* password = "12345689"; 

const char* mqtt_server = "18597cd464464ab4b3c1c5d4bf9b070e.s1.eu.hivemq.cloud"; 
const int mqtt_port = 8883;
const char* mqtt_user = "dodanhtoan"; 
const char* mqtt_pass = "Toan0809"; 

const char* mqtt_topic_pub = "esp8266/status";
const char* mqtt_topic_sub = "esp8266/client";

#define SLAVE_ADDRESS 0x08
#define SDA_PIN 21
#define SCL_PIN 22

WiFiClientSecure espClient;
PubSubClient client(espClient);

// Cập nhật Struct để chứa đủ dữ liệu
struct UnoStatus {
  int fan_pwm;
  int led_state;
  int temp;
  int hum;
  int pir;
  int fan_auto;
  int led_auto;
};
UnoStatus currentStatus;

unsigned long lastMsg = 0;
const long interval = 2000;

void callback(char* topic, byte* payload, unsigned int length) {
  String message = "";
  for (unsigned int i = 0; i < length; i++) message += (char)payload[i];
  if (message.length() > 0) {
    char cmd = message.charAt(0);
    Wire.beginTransmission(SLAVE_ADDRESS);
    Wire.write(cmd); 
    Wire.endTransmission();
  }
}

void reconnect() {
  while (!client.connected()) {
    String clientId = "ESP32_GW_" + String(random(0xffff), HEX);
    if (client.connect(clientId.c_str(), mqtt_user, mqtt_pass)) { 
      client.publish(mqtt_topic_pub, "{\"status\":\"connected\"}");
      client.subscribe(mqtt_topic_sub); 
    } else {
      delay(5000);
    }
  }
}

bool requestUnoData() {
    // Yêu cầu 7 byte
    Wire.requestFrom(SLAVE_ADDRESS, 7);
    if (Wire.available() == 7) {
        currentStatus.fan_pwm   = Wire.read(); 
        currentStatus.led_state = Wire.read();
        currentStatus.temp      = Wire.read();
        currentStatus.hum       = Wire.read();
        currentStatus.pir       = Wire.read();
        currentStatus.fan_auto  = Wire.read();
        currentStatus.led_auto  = Wire.read();
        return true;
    } 
    return false;
}

void publishData() {
    // Tạo JSON đầy đủ
    StaticJsonDocument<300> doc;
    doc["fan_pwm"] = currentStatus.fan_pwm;
    doc["led_state"] = currentStatus.led_state;
    doc["temp"] = currentStatus.temp;
    doc["hum"] = currentStatus.hum;
    doc["pir"] = currentStatus.pir; // 1: Có người, 0: Không
    doc["fan_mode"] = currentStatus.fan_auto ? "Auto" : "Manual";
    doc["led_mode"] = currentStatus.led_auto ? "Auto" : "Manual";
    
    char jsonBuffer[300];
    serializeJson(doc, jsonBuffer);
    client.publish(mqtt_topic_pub, jsonBuffer);
}

void setup() {
    Serial.begin(115200);
    Wire.begin(SDA_PIN, SCL_PIN);
    WiFi.mode(WIFI_STA);
    WiFi.begin(ssid, password);
    while (WiFi.status() != WL_CONNECTED) delay(500);
    
    espClient.setInsecure();
    client.setServer(mqtt_server, mqtt_port);
    client.setCallback(callback);
}

void loop() {
    if (!client.connected()) reconnect();
    client.loop();
    unsigned long now = millis();
    if (now - lastMsg > interval) {
        lastMsg = now;
        if (requestUnoData()) publishData();
    }
} 