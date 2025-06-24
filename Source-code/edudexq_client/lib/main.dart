import 'package:fluent_ui/fluent_ui.dart' hide Page;
import 'package:flutter/foundation.dart';
import 'package:flutter_acrylic/flutter_acrylic.dart' as flutter_acrylic;
import 'package:provider/provider.dart';
import 'package:system_theme/system_theme.dart';
import 'package:window_manager/window_manager.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:mutex/mutex.dart';
import 'dart:io';
import 'dart:async';

import 'screens/splash_screen.dart';
import 'theme.dart';
import 'screens/dashboard/dashboard_screen.dart';
import 'screens/login.dart';
import 'screens/result_screen.dart';

const String appTitle = 'EduDexQ';

// Mutex toàn cục cho ứng dụng
final _appMutex = Mutex();
bool _hasLock = false;

bool get isDesktop {
  if (kIsWeb) return false;
  return [
    TargetPlatform.windows,
    TargetPlatform.linux,
    TargetPlatform.macOS,
  ].contains(defaultTargetPlatform);
}

void main(List<String> args) async {
  WidgetsFlutterBinding.ensureInitialized();

  // Xóa cache đăng nhập mỗi lần mở app
  final prefs = await SharedPreferences.getInstance();
  await prefs.remove('thiSinh');
  await prefs.remove('kyThi');
  await prefs.remove('deThi');
  await prefs.remove('ketQua');
  await prefs.remove('server_url');
  await prefs.remove('server_timeout');

  await _initializeSingleInstance();
  if (!_hasLock) {
    if (Platform.isWindows) {
      await windowManager.show();
      await windowManager.focus();
    }
    exit(0);
  }

  await SharedPreferences.getInstance();
  final appTheme = AppTheme();
  await appTheme.loadSettings();

  if (!kIsWeb &&
      [TargetPlatform.windows, TargetPlatform.android]
          .contains(defaultTargetPlatform)) {
    SystemTheme.accentColor.load();
  }

  if (!kIsWeb && isDesktop) {
    await flutter_acrylic.Window.initialize();
    if (defaultTargetPlatform == TargetPlatform.windows) {
      await flutter_acrylic.Window.hideWindowControls();
    }
    await WindowManager.instance.ensureInitialized();
    windowManager.waitUntilReadyToShow().then((_) async {
      await windowManager.setTitleBarStyle(
        TitleBarStyle.hidden,
        windowButtonVisibility: false,
      );
      await windowManager.setMinimumSize(const Size(500, 600));
      await windowManager.setSize(const Size(1000, 700));
      await windowManager.center();
      await windowManager.maximize();
      await windowManager.show();
      await windowManager.setPreventClose(true);
      await windowManager.setSkipTaskbar(false);

      // Thêm handler khi đóng cửa sổ
      await windowManager.setPreventClose(true);
      // windowManager.addListener(WindowListener(
      //   onWindowClose: () async {
      //     await _connectionService.dispose();
      //     if (await windowManager.isPreventClose()) {
      //       // ... existing close confirmation code ...
      //     }
      //   },
      // ));
    });
  }

  // Khởi chạy ứng dụng
  runApp(ChangeNotifierProvider.value(
    value: appTheme,
    child: const MyApp(),
  ));
}

Future<void> _initializeSingleInstance() async {
  try {
    // Thử acquire lock
    _hasLock = await _appMutex.protect(() async {
      final prefs = await SharedPreferences.getInstance();
      final lastPing = prefs.getInt('app_last_ping') ?? 0;
      final now = DateTime.now().millisecondsSinceEpoch;

      // Nếu ping cuối cùng > 5 giây, coi như instance cũ đã đóng
      if (now - lastPing > 5000) {
        // Lưu thời gian ping mới
        await prefs.setInt('app_last_ping', now);
        return true;
      }
      return false;
    });

    // Nếu có lock, bắt đầu ping định kỳ
    if (_hasLock) {
      Timer.periodic(const Duration(seconds: 3), (timer) async {
        final prefs = await SharedPreferences.getInstance();
        await prefs.setInt(
            'app_last_ping', DateTime.now().millisecondsSinceEpoch);
      });
    }
  } catch (e) {
    stderr.writeln('Lỗi khi khởi tạo single instance: $e');
    _hasLock = false;
  }
}

class MyApp extends StatelessWidget {
  const MyApp({super.key});

  @override
  Widget build(BuildContext context) {
    final appTheme = context.watch<AppTheme>();
    return FluentApp(
      title: appTitle,
      themeMode: appTheme.mode,
      debugShowCheckedModeBanner: false,
      color: appTheme.color,
      darkTheme: FluentThemeData(
        brightness: Brightness.dark,
        accentColor: appTheme.color,
        visualDensity: VisualDensity.standard,
        focusTheme: FocusThemeData(
          glowFactor: is10footScreen(context) ? 2.0 : 0.0,
        ),
      ),
      theme: FluentThemeData(
        accentColor: appTheme.color,
        visualDensity: VisualDensity.standard,
        focusTheme: FocusThemeData(
          glowFactor: is10footScreen(context) ? 2.0 : 0.0,
        ),
      ),
      locale: appTheme.locale,
      builder: (context, child) {
        return Directionality(
          textDirection: appTheme.textDirection,
          child: NavigationPaneTheme(
            data: const NavigationPaneThemeData(),
            child: child!,
          ),
        );
      },
      home: const SplashScreen(),
      routes: {
        '/home': (context) => const DashboardScreen(),
        '/ket-qua': (context) => const ResultScreen(),
        '/login': (context) => const LoginScreen(),
      },
    );
  }
}
