# Android WebView (rápido)

Projeto Android nativo mínimo para abrir o sistema em um WebView.

## URL carregada

Definida em `app/src/main/res/values/strings.xml`:

- `webview_url`: `http://10.0.2.2:8500`

`10.0.2.2` funciona no emulador Android para acessar o host local.
Para celular físico, troque pelo IP da máquina na rede local (ex.: `http://192.168.0.20:8500`).

## Gerar APK no Android Studio

1. Abra a pasta `android-webview` no Android Studio.
2. Aguarde o Gradle sync.
3. Vá em `Build > Build Bundle(s) / APK(s) > Build APK(s)`.
4. O APK sai em `app/build/outputs/apk/debug/app-debug.apk`.
