#include <Wire.h>
#include <DHT.h> 

// --- CẤU HÌNH ---
#define SLAVE_ADDRESS 0x08 

// PINS
#define FAN_EN_PIN  11 
#define FAN_IN1_PIN 10  
#define FAN_IN2_PIN 9   
#define LED_OUT     7   

#define BTN1_FAN_PIN  12
#define BTN2_LED_PIN  8 
#define LDR_PIN       2 
#define PIR_PIN       4  
#define DHT_PIN       6 

#define DHTTYPE DHT11
DHT dht(DHT_PIN, DHTTYPE);

// --- BIẾN TOÀN CỤC ---
int lastFanButtonState = HIGH; 
int lastLedButtonState = HIGH; 

unsigned long lastDhtReadTime = 0;
const unsigned long dhtInterval = 2000;
unsigned long lastLogicRunTime = 0;
const unsigned long logicInterval = 500; 

// --- CẤU HÌNH THỜI GIAN TRỄ (BẠN CHỈNH Ở ĐÂY) ---
unsigned long fanOffTime = 0; 
unsigned long ledManualTime = 0; // [MỚI] Biến đếm giờ cho LED
const unsigned long autoDelayTime = 10000; // [SỬA] Tăng lên 10000ms = 10 giây

// Trạng thái thiết bị
int btn1State = 0; 
bool isFanOn = false;
bool isLedOn = false; 
int fanPwmLevel = 0; 

// Chế độ (Mặc định là Auto)
bool isFanAuto = true;
bool isLedAuto = true;

// Dữ liệu cảm biến
float currentTemperature = 0.0;
float currentHumidity = 0.0; 
bool isPersonDetected = false; 

// Ngưỡng nhiệt độ
const float FAN_TEMP_30 = 28.0; 
const float FAN_TEMP_33 = 30.0;
const float FAN_TEMP_36 = 32.0;
const float FIRE_ALERT_TEMP = 50.0;
const float FIRE_ALERT_RESET = 45.0; 

void setFanSpeed(int pwmPercent) {
    if (pwmPercent == 0) {
        digitalWrite(FAN_IN1_PIN, LOW); 
        digitalWrite(FAN_IN2_PIN, LOW); 
        analogWrite(FAN_EN_PIN, 0);
        isFanOn = false;
        fanPwmLevel = 0;
    } else {
        int pwmValue = map(pwmPercent, 0, 100, 0, 255);
        digitalWrite(FAN_IN1_PIN, HIGH); 
        digitalWrite(FAN_IN2_PIN, LOW);  
        analogWrite(FAN_EN_PIN, pwmValue); 
        isFanOn = true;
        fanPwmLevel = pwmPercent;
    }
}

void setLedState(bool state) {
    if (currentTemperature <= FIRE_ALERT_TEMP) { 
        isLedOn = state;
        digitalWrite(LED_OUT, isLedOn ? HIGH : LOW);
    }
}

void readSensors() {
    float t = dht.readTemperature();
    float h = dht.readHumidity();
    
    if (!isnan(t)) currentTemperature = t;
    if (!isnan(h)) currentHumidity = h;

    isPersonDetected = digitalRead(PIR_PIN); 
}

// --- LOGIC TỰ ĐỘNG ---
void handleFanAutoLogic() {
    if (!isFanAuto || currentTemperature > FIRE_ALERT_TEMP) return; 

    int newPwm = 0;
    if (isPersonDetected) {
        if (currentTemperature >= FAN_TEMP_36) newPwm = 90;
        else if (currentTemperature >= FAN_TEMP_33) newPwm = 60;
        else if (currentTemperature >= FAN_TEMP_30) newPwm = 30;
    } 
    if (newPwm != fanPwmLevel) setFanSpeed(newPwm);
}

void handleLedAutoLogic() {
    if (!isLedAuto || currentTemperature > FIRE_ALERT_TEMP) return; 
    bool isDark = digitalRead(LDR_PIN) == HIGH; 
    if (isDark && isPersonDetected) {
        if (!isLedOn) setLedState(true);
    } else {
        if (isLedOn) setLedState(false);
    }
}

void handleFireAlert() {
    if (currentTemperature > FIRE_ALERT_TEMP) {
        if (isFanOn) setFanSpeed(0);
        if ((millis() / 200) % 2 == 0) digitalWrite(LED_OUT, HIGH);
        else digitalWrite(LED_OUT, LOW);
    } else if (currentTemperature <= FIRE_ALERT_RESET) { 
        digitalWrite(LED_OUT, isLedOn ? HIGH : LOW);
    }
}

// --- [QUAN TRỌNG] HÀM KIỂM TRA THỜI GIAN TRỄ ---
void periodicTasks() {
    // 1. Kiểm tra trễ cho QUẠT
    if (!isFanAuto && fanOffTime != 0) {
        if (millis() - fanOffTime >= autoDelayTime) {
            isFanAuto = true; fanOffTime = 0; 
        }
    }

    // 2. [MỚI] Kiểm tra trễ cho LED
    // Nếu đang chỉnh tay (Manual) và có đặt thời gian
    if (!isLedAuto && ledManualTime != 0) {
        // Nếu đã hết thời gian chờ -> Quay về Auto
        if (millis() - ledManualTime >= autoDelayTime) {
            isLedAuto = true; 
            ledManualTime = 0; 
        }
    }

    handleFireAlert();
    if (currentTemperature <= FIRE_ALERT_TEMP) {
        if (isFanAuto) handleFanAutoLogic();
        if (isLedAuto) handleLedAutoLogic();
    }
}

// --- NÚT NHẤN ---
void handleBtn1FanPolling() {
    int currentButtonState = digitalRead(BTN1_FAN_PIN);
    if (lastFanButtonState == HIGH && currentButtonState == LOW) {
        if (currentTemperature <= FIRE_ALERT_TEMP) {
            delay(50); 
            if (digitalRead(BTN1_FAN_PIN) == LOW) {
                isFanAuto = false; fanOffTime = 0; 
                btn1State++;
                if (btn1State > 4) btn1State = 1;
                int pwmValue = 0;
                switch (btn1State) {
                    case 1: pwmValue = 30; break; 
                    case 2: pwmValue = 60; break; 
                    case 3: pwmValue = 90; break; 
                    case 4: pwmValue = 0; btn1State = 0; fanOffTime = millis(); break; 
                }
                setFanSpeed(pwmValue);
            }
        }
    }
    lastFanButtonState = currentButtonState;
}

void handleBtn2LedPolling() {
    int currentButtonState = digitalRead(BTN2_LED_PIN);
    if (lastLedButtonState == HIGH && currentButtonState == LOW) {
        if (currentTemperature <= FIRE_ALERT_TEMP) {
            delay(50);
            if (digitalRead(BTN2_LED_PIN) == LOW) {
                // [SỬA] Logic nút bấm LED
                isLedAuto = false;          // Chuyển sang chỉnh tay
                setLedState(!isLedOn);      // Đảo trạng thái đèn
                ledManualTime = millis();   // [QUAN TRỌNG] Bắt đầu đếm giờ chờ
            }
        }
    }
    lastLedButtonState = currentButtonState;
}

// --- I2C GIAO TIẾP ---
void requestEvent() {
    byte data[7];
    data[0] = (byte)fanPwmLevel;
    data[1] = isLedOn ? 1 : 0;
    data[2] = (byte)currentTemperature; 
    data[3] = (byte)currentHumidity;    
    data[4] = isPersonDetected ? 1 : 0; 
    data[5] = isFanAuto ? 1 : 0;        
    data[6] = isLedAuto ? 1 : 0;        
    Wire.write(data, 7);
}

void receiveEvent(int byteCount) {
    if (byteCount > 0) {
        char cmd = Wire.read();
        
        // Nhận lệnh từ ESP32
        if (cmd == 'F') { setFanSpeed(0); btn1State = 0; isFanAuto = false; }
        else if (cmd == '3') { setFanSpeed(30); btn1State = 1; isFanAuto = false; }
        else if (cmd == '6') { setFanSpeed(60); btn1State = 2; isFanAuto = false; }
        else if (cmd == '9') { setFanSpeed(90); btn1State = 3; isFanAuto = false; }
        
        // Điều khiển LED từ Web
        else if (cmd == 'O') { 
            setLedState(true); 
            isLedAuto = false; 
            ledManualTime = millis(); // [MỚI] Cũng kích hoạt đếm giờ khi chỉnh từ Web
        }
        else if (cmd == 'f') { 
            setLedState(false); 
            isLedAuto = false; 
            ledManualTime = millis(); // [MỚI]
        }
        
        if (!isFanAuto && cmd == 'F') fanOffTime = millis();
    }
}

void setup() {
    pinMode(FAN_EN_PIN, OUTPUT); pinMode(FAN_IN1_PIN, OUTPUT); pinMode(FAN_IN2_PIN, OUTPUT); pinMode(LED_OUT, OUTPUT);
    pinMode(BTN1_FAN_PIN, INPUT_PULLUP); pinMode(BTN2_LED_PIN, INPUT_PULLUP); pinMode(LDR_PIN, INPUT); pinMode(PIR_PIN, INPUT);
    dht.begin();
    Wire.begin(SLAVE_ADDRESS);
    Wire.onRequest(requestEvent);
    Wire.onReceive(receiveEvent);
    setFanSpeed(0); setLedState(false); readSensors();
}

void loop() {
    unsigned long currentMillis = millis();
    if (currentMillis - lastDhtReadTime >= dhtInterval) {
        readSensors();
        lastDhtReadTime = currentMillis;
    }
    if (currentMillis - lastLogicRunTime >= logicInterval) {
        periodicTasks();
        lastLogicRunTime = currentMillis;
    }
    handleBtn1FanPolling(); 
    handleBtn2LedPolling();
}