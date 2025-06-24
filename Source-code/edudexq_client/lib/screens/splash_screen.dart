import 'package:fluent_ui/fluent_ui.dart';
import 'package:window_manager/window_manager.dart';
import '../theme.dart';
import 'login.dart';
import 'dart:async';
import 'package:http/http.dart' as http;
import 'settings.dart';
import 'package:provider/provider.dart';
import 'dart:convert';
import 'dart:io' show Platform, File;
import 'package:shared_preferences/shared_preferences.dart';
import 'package:flutter/foundation.dart';

class SplashScreen extends StatefulWidget {
  const SplashScreen({super.key});

  @override
  State<SplashScreen> createState() => _SplashScreenState();
}

class _SplashScreenState extends State<SplashScreen> with WindowListener {
  bool _isLoading = false;
  String? _errorMessage;
  bool _isInitializing = true;
  String? _serverUrl;
  int _timeout = 5000;

  @override
  void initState() {
    windowManager.addListener(this);
    super.initState();
    _initialize();
  }

  Future<void> _initialize() async {
    setState(() => _isInitializing = true);
    await Future.delayed(const Duration(seconds: 1));
    await _loadServerUrlAndCheck();
    setState(() => _isInitializing = false);
  }

  Future<void> _loadServerUrlAndCheck() async {
    setState(() {
      _isLoading = true;
      _errorMessage = null;
    });
    try {
      final prefs = await SharedPreferences.getInstance();
      final cachedUrl = prefs.getString('server_url');
      final cachedTimeout = prefs.getInt('server_timeout');
      if (cachedUrl != null) {
        _serverUrl = cachedUrl;
        _timeout = cachedTimeout ?? 5000;
        await _checkConnection();
        return;
      }
      // Nếu chưa có cache thì đọc file config
      await _loadConfigAndCheck();
    } catch (e) {
      setState(() {
        _errorMessage = 'Lỗi tải cấu hìnhr: $e';
      });
    } finally {
      setState(() {
        _isLoading = false;
      });
    }
  }

  Future<void> _loadConfigAndCheck() async {
    setState(() {
      _isLoading = true;
      _errorMessage = null;
    });

    String defaultUrl = 'http://edudexq.local/API';
    int defaultTimeout = 15000;

    try {
      // Nếu không phải desktop/mobile thì luôn dùng mặc định
      if (kIsWeb) {
        _serverUrl = defaultUrl;
        _timeout = defaultTimeout;
        final prefs = await SharedPreferences.getInstance();
        await prefs.setString('server_url', _serverUrl!);
        await prefs.setInt('server_timeout', _timeout);
        await _checkConnection();
        return;
      }

      final configFile = File('copecute/config.json');
      if (!await configFile.exists()) {
        // Nếu không có file config, dùng mặc định
        _serverUrl = defaultUrl;
        _timeout = defaultTimeout;
        final prefs = await SharedPreferences.getInstance();
        await prefs.setString('server_url', _serverUrl!);
        await prefs.setInt('server_timeout', _timeout);
        await _checkConnection();
        return;
      }

      final configContent = await configFile.readAsString();
      dynamic config;
      try {
        config = json.decode(configContent);
      } catch (e) {
        // Nếu lỗi parse JSON, dùng mặc định
        _serverUrl = defaultUrl;
        _timeout = defaultTimeout;
        final prefs = await SharedPreferences.getInstance();
        await prefs.setString('server_url', _serverUrl!);
        await prefs.setInt('server_timeout', _timeout);
        await _checkConnection();
        return;
      }

      // Kiểm tra trường server_url
      if (config is! Map ||
          !config.containsKey('server_url') ||
          config['server_url'] == null ||
          config['server_url'].toString().isEmpty) {
        // Nếu thiếu hoặc sai, dùng mặc định
        _serverUrl = defaultUrl;
      } else {
        _serverUrl = config['server_url'].toString();
      }

      // Kiểm tra timeout
      if (config is Map && config.containsKey('timeout')) {
        try {
          _timeout = int.parse(config['timeout'].toString());
        } catch (_) {
          _timeout = defaultTimeout;
        }
      } else {
        _timeout = defaultTimeout;
      }

      // Lưu vào cache
      final prefs = await SharedPreferences.getInstance();
      await prefs.setString('server_url', _serverUrl!);
      await prefs.setInt('server_timeout', _timeout);
      await _checkConnection();
    } catch (e) {
      setState(() {
        _errorMessage =
            'Lỗi tải cấu hình máy chủ: ${e.runtimeType}: ${e.toString()}';
      });
    } finally {
      setState(() {
        _isLoading = false;
      });
    }
  }

  Future<void> _checkConnection() async {
    if (_serverUrl == null) {
      setState(() {
        _errorMessage = 'Chưa có địa chỉ máy chủ.';
      });
      return;
    }
    setState(() {
      _isLoading = true;
      _errorMessage = null;
    });
    try {
      final response = await http
          .get(Uri.parse('${_serverUrl!}/index.php'))
          .timeout(Duration(milliseconds: _timeout));
      if (response.statusCode == 200) {
        if (mounted) {
          Navigator.pushReplacement(
            context,
            FluentPageRoute(builder: (context) => const LoginScreen()),
          );
        }
      } else {
        setState(() {
          _errorMessage =
              'Kết nối thất bại: Mã trạng thái ${response.statusCode}';
        });
      }
    } on TimeoutException {
      setState(() {
        _errorMessage = 'Kết nối quá thời gian chờ.';
      });
    } catch (e) {
      setState(() {
        _errorMessage = 'Không thể kết nối tới máy chủ';
      });
    } finally {
      setState(() {
        _isLoading = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    if (_isInitializing) {
      return NavigationView(
        content: ScaffoldPage(
          padding: EdgeInsets.zero,
          content: Center(
            child: Column(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                Image.asset(
                  'assets/logo.png',
                  width: 200,
                  height: 200,
                ),
                const SizedBox(height: 32),
                const ProgressRing(),
                const SizedBox(height: 16),
                const SizedBox(height: 20),
                Text(
                  'EduDexQ',
                  style: FluentTheme.of(context).typography.titleLarge,
                ),
              ],
            ),
          ),
        ),
      );
    }
    return NavigationView(
      appBar: NavigationAppBar(
        automaticallyImplyLeading: false,
        title: const DragToMoveArea(
          child: Align(
            alignment: AlignmentDirectional.centerStart,
          ),
        ),
        actions: Row(
          mainAxisAlignment: MainAxisAlignment.end,
          children: [
            IconButton(
              icon: const Icon(FluentIcons.settings),
              onPressed: () {
                Navigator.push(
                  context,
                  FluentPageRoute(
                    builder: (context) => const Settings(
                      showBackButton: true,
                      showDisconnectButton: false,
                    ),
                  ),
                );
              },
            ),
            Align(
              alignment: AlignmentDirectional.centerEnd,
              child: Padding(
                padding: const EdgeInsetsDirectional.only(end: 8.0),
                child: ToggleSwitch(
                  content: const Text('Chế độ tối'),
                  checked: FluentTheme.of(context).brightness.isDark,
                  onChanged: (v) {
                    // Nếu cần thay đổi theme, hãy xử lý ở đây
                  },
                ),
              ),
            ),
            const WindowButtons(),
          ],
        ),
      ),
      content: ScaffoldPage(
        padding: EdgeInsets.zero,
        content: Center(
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Image.asset(
                'assets/logo.png',
                width: 200,
                height: 200,
              ),
              const SizedBox(height: 20),
              FilledButton(
                onPressed: _isLoading ? null : _loadConfigAndCheck,
                child: _isLoading
                    ? const Row(
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          SizedBox(
                            width: 16,
                            height: 16,
                            child: ProgressRing(),
                          ),
                          SizedBox(width: 8),
                          Text('Đang kiểm tra...'),
                        ],
                      )
                    : const Text('Kiểm tra kết nối'),
              ),
              if (_errorMessage != null) ...[
                const SizedBox(height: 10),
                Text(
                  _errorMessage!,
                  style: TextStyle(color: Colors.red),
                ),
              ],
              const SizedBox(height: 20),
              Text(
                'EduDex Quiz',
                style: FluentTheme.of(context).typography.titleLarge,
              ),
            ],
          ),
        ),
      ),
    );
  }

  @override
  void dispose() {
    windowManager.removeListener(this);
    super.dispose();
  }

  @override
  void onWindowClose() async {
    bool isPreventClose = await windowManager.isPreventClose();
    if (isPreventClose && mounted) {
      showDialog(
        context: context,
        builder: (_) {
          return ContentDialog(
            title: const Text('Xác nhận đóng'),
            content: const Text('Bạn có chắc chắn muốn đóng cửa sổ này?'),
            actions: [
              FilledButton(
                child: const Text('Có'),
                onPressed: () async {
                  Navigator.pop(context);
                  await windowManager.setPreventClose(false);
                  await windowManager.close();
                },
              ),
              Button(
                child: const Text('Không'),
                onPressed: () {
                  Navigator.pop(context);
                },
              ),
            ],
          );
        },
      );
    }
  }
}

class WindowButtons extends StatelessWidget {
  const WindowButtons({super.key});

  @override
  Widget build(BuildContext context) {
    final theme = FluentTheme.of(context);
    return SizedBox(
      width: 138,
      height: 50,
      child: WindowCaption(
        brightness: theme.brightness,
        backgroundColor: Colors.transparent,
      ),
    );
  }
}
