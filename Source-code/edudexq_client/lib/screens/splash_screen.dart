import 'package:fluent_ui/fluent_ui.dart';
import 'package:window_manager/window_manager.dart';
import '../theme.dart';
import 'login.dart';
import 'dart:async';
import 'package:http/http.dart' as http;
import 'settings.dart';
import 'package:provider/provider.dart';
import 'dart:convert';
import 'dart:io' show Platform, File, Directory;
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
  double _opacity = 0.0;
  String? pingStatus;
  String? pingMessage;

  @override
  void initState() {
    windowManager.addListener(this);
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      setState(() => _opacity = 1.0);
    });
    _initConfigAndNavigate();
  }

  Future<void> _initConfigAndNavigate() async {
    String? serverUrl;
    int? timeout;
    String? adminPassword;
    try {
      if (kIsWeb) {
        serverUrl = 'http://edudexq.local/API';
        timeout = 15000;
        adminPassword = 'copecute123';
      } else {
        final configFile = File('copecute/copecute.edudex');
        if (await configFile.exists()) {
          final configContent = await configFile.readAsString();
          dynamic config;
          try {
            final decoded = utf8.decode(base64.decode(configContent));
            config = json.decode(decoded);
          } catch (_) {
            config = null;
          }
          if (config is Map &&
              config.containsKey('server_url') &&
              config['server_url'] != null &&
              config['server_url'].toString().isNotEmpty) {
            serverUrl = config['server_url'].toString();
          }
          if (config is Map && config.containsKey('timeout')) {
            try {
              timeout = int.parse(config['timeout'].toString());
            } catch (_) {
              timeout = 15000;
            }
          } else {
            timeout = 15000;
          }
          if (config is Map && config.containsKey('admin_password')) {
            adminPassword = config['admin_password'].toString();
          } else {
            adminPassword = 'copecute123';
          }
        } else {
          await _showConfigInputDialog();
          return;
        }
      }
      final prefs = await SharedPreferences.getInstance();
      await prefs.setString('server_url', serverUrl!);
      await prefs.setInt('server_timeout', timeout!);
      await prefs.setString('admin_password', adminPassword!);
    } catch (_) {
      await _showConfigInputDialog();
      return;
    }
    if (mounted) {
      await Future.delayed(const Duration(seconds: 2));
      setState(() => _opacity = 0.0);
      await Future.delayed(const Duration(milliseconds: 500));
      if (mounted) {
        Navigator.pushReplacement(
          context,
          FluentPageRoute(builder: (context) => const LoginScreen()),
        );
      }
    }
  }

  Future<void> _showConfigInputDialog() async {
    final urlController = TextEditingController();
    final timeoutController = TextEditingController(text: '15000');
    final adminPasswordController = TextEditingController();
    void checkServerConnection(StateSetter setState) async {
      setState(() {
        pingStatus = 'checking';
        pingMessage = null;
      });
      final url = urlController.text.trim();
      if (url.isEmpty) {
        setState(() {
          pingStatus = 'fail';
          pingMessage = 'Vui lòng nhập địa chỉ máy chủ.';
        });
        return;
      }
      try {
        final uri = Uri.parse(
            url.endsWith('/') ? url + 'index.php' : url + '/index.php');
        final response =
            await http.get(uri).timeout(const Duration(seconds: 5));
        if (response.statusCode == 200) {
          final data = json.decode(response.body);
          if (data is Map && data['message'] == 'hello world copecute') {
            setState(() {
              pingStatus = 'success';
              pingMessage = 'Kết nối thành công!';
            });
            return;
          }
        }
        setState(() {
          pingStatus = 'fail';
          pingMessage = 'Phản hồi không hợp lệ.';
        });
      } catch (e) {
        setState(() {
          pingStatus = 'fail';
          pingMessage = 'Lỗi: $e';
        });
      }
    }

    bool valid = false;
    while (!valid && mounted) {
      valid = await showDialog<bool>(
            context: context,
            barrierDismissible: false,
            builder: (context) => StatefulBuilder(
              builder: (context, setState) => ContentDialog(
                title: const Text('Thiết lập máy chủ'),
                content: Column(
                  mainAxisSize: MainAxisSize.min,
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Text('Nhập địa chỉ máy chủ http(s):'),
                    const SizedBox(height: 8),
                    Row(
                      children: [
                        Expanded(
                          child: TextBox(
                            controller: urlController,
                            placeholder: 'http://edudexq.local/API',
                          ),
                        ),
                        const SizedBox(width: 8),
                        FilledButton(
                          child: const Text('Kiểm tra kết nối'),
                          onPressed: () => checkServerConnection(setState),
                        ),
                      ],
                    ),
                    if (pingStatus == 'checking') ...[
                      SizedBox(height: 8),
                      ProgressRing(),
                      SizedBox(height: 4),
                      Text('Đang kiểm tra kết nối...'),
                    ] else if (pingStatus == 'success') ...[
                      SizedBox(height: 8),
                      Icon(FluentIcons.accept, color: Colors.green),
                      SizedBox(height: 4),
                      Text(pingMessage ?? 'Kết nối thành công!',
                          style: TextStyle(color: Colors.green)),
                    ] else if (pingStatus == 'fail') ...[
                      SizedBox(height: 8),
                      Icon(FluentIcons.error, color: Colors.red),
                      SizedBox(height: 4),
                      Text(pingMessage ?? 'Kết nối thất bại!',
                          style: TextStyle(color: Colors.red)),
                    ],
                    const SizedBox(height: 16),
                    const Text('Timeout (ms):'),
                    const SizedBox(height: 8),
                    TextBox(
                      controller: timeoutController,
                      placeholder: '15000',
                      keyboardType: TextInputType.number,
                    ),
                    const SizedBox(height: 16),
                    const Text('Mật khẩu cấu hình:'),
                    const SizedBox(height: 8),
                    TextBox(
                      controller: adminPasswordController,
                      placeholder: 'Nhập mật khẩu cấu hình',
                      obscureText: true,
                    ),
                  ],
                ),
                actions: [
                  FilledButton(
                    child: const Text('Lưu'),
                    onPressed: () {
                      final url = urlController.text.trim();
                      final timeout =
                          int.tryParse(timeoutController.text.trim()) ?? 15000;
                      final adminPassword = adminPasswordController.text.trim();
                      if (url.isNotEmpty && adminPassword.isNotEmpty) {
                        final config = {
                          'server_url': url,
                          'timeout': timeout,
                          'admin_password': adminPassword,
                        };
                        final dir = Directory('copecute');
                        dir.create(recursive: true).then((_) async {
                          final file = File('copecute/copecute.edudex');
                          final encoded =
                              base64.encode(utf8.encode(json.encode(config)));
                          await file.writeAsString(encoded, flush: true);
                          final prefs = await SharedPreferences.getInstance();
                          await prefs.setString('server_url', url);
                          await prefs.setInt('server_timeout', timeout);
                          await prefs.setString(
                              'admin_password', adminPassword);
                          Navigator.pop(context, true);
                        });
                      } else {
                        // Không hợp lệ, không đóng dialog
                      }
                    },
                  ),
                ],
              ),
            ),
          ) ??
          false;
      if (!valid) {
        // Nếu người dùng không nhập url hoặc mật khẩu, lặp lại dialog
        await Future.delayed(const Duration(milliseconds: 100));
      }
    }
    // Sau khi lưu xong, tiếp tục quy trình splash
    if (mounted) {
      await Future.delayed(const Duration(seconds: 2));
      setState(() => _opacity = 0.0);
      await Future.delayed(const Duration(milliseconds: 500));
      if (mounted) {
        Navigator.pushReplacement(
          context,
          FluentPageRoute(builder: (context) => const LoginScreen()),
        );
      }
    }
  }

  @override
  Widget build(BuildContext context) {
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
          child: AnimatedOpacity(
            opacity: _opacity,
            duration: const Duration(milliseconds: 500),
            child: Column(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                Image.asset(
                  'assets/logo.png',
                  width: 200,
                  height: 200,
                ),
                const SizedBox(height: 20),
                Text(
                  'EduDex Quiz',
                  style: FluentTheme.of(context).typography.titleLarge,
                ),
              ],
            ),
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
