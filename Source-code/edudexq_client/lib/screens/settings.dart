import 'package:flutter/foundation.dart';
import 'package:flutter/services.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'dart:io';
import 'dart:convert';
import 'package:http/http.dart' as http;

import 'package:fluent_ui/fluent_ui.dart';
import 'package:flutter_acrylic/flutter_acrylic.dart';
import 'package:provider/provider.dart';
import '../screens/splash_screen.dart';

import '../theme.dart';
import '../widgets/page.dart';

const List<String> accentColorNames = [
  'System',
  'Yellow',
  'Orange',
  'Red',
  'Magenta',
  'Purple',
  'Blue',
  'Teal',
  'Green',
];

bool get kIsWindowEffectsSupported {
  return false;
}

const List<Locale> supportedLocales = [
  Locale('vi', 'VN'),
  Locale('en', 'US'),
];

const String configPath = 'copecute/copecute.edudex';

class Settings extends StatefulWidget {
  final bool showBackButton;
  final bool showDisconnectButton;

  const Settings({
    super.key,
    this.showBackButton = false,
    this.showDisconnectButton = true,
  });

  @override
  State<Settings> createState() => _SettingsState();
}

class _SettingsState extends State<Settings> with PageMixin {
  bool _showAdvancedSettings = false;
  final _passwordController = TextEditingController();
  bool _obscurePassword = true;

  void _showPasswordDialog() {
    showDialog(
      context: context,
      builder: (context) => ContentDialog(
        title: const Text('Nhập mật khẩu'),
        content: StatefulBuilder(
          builder: (context, setState) => SizedBox(
            height: 32,
            child: TextBox(
              controller: _passwordController,
              placeholder: 'Nhập mật khẩu cấu hình',
              obscureText: _obscurePassword,
              suffix: IconButton(
                icon: Icon(
                  _obscurePassword ? FluentIcons.hide : FluentIcons.red_eye,
                ),
                onPressed: () {
                  setState(() => _obscurePassword = !_obscurePassword);
                },
              ),
            ),
          ),
        ),
        actions: [
          FilledButton(
            child: const Text('Xác nhận'),
            onPressed: () async {
              final inputPassword = _passwordController.text;
              String? adminPassword;
              try {
                final file = File(configPath);
                if (await file.exists()) {
                  final content = await file.readAsString();
                  final decoded = utf8.decode(base64.decode(content));
                  final config = json.decode(decoded);
                  adminPassword = config['admin_password']?.toString() ?? '';
                }
              } catch (_) {}
              if (inputPassword == adminPassword &&
                  adminPassword != null &&
                  adminPassword.isNotEmpty) {
                setState(() => _showAdvancedSettings = true);
                Navigator.pop(context);
              } else {
                showDialog(
                  context: context,
                  builder: (context) => ContentDialog(
                    title: const Text('Lỗi'),
                    content: const Text('Mật khẩu không đúng'),
                    actions: [
                      Button(
                        child: const Text('Đóng'),
                        onPressed: () => Navigator.pop(context),
                      ),
                    ],
                  ),
                );
              }
              _passwordController.clear();
            },
          ),
          Button(
            child: const Text('Hủy'),
            onPressed: () {
              Navigator.pop(context);
              _passwordController.clear();
            },
          ),
        ],
      ),
    );
  }

  @override
  void dispose() {
    _passwordController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    assert(debugCheckHasMediaQuery(context));
    final appTheme = context.watch<AppTheme>();
    const spacer = SizedBox(height: 10.0);
    const biggerSpacer = SizedBox(height: 40.0);

    return NavigationView(
      appBar: NavigationAppBar(
        automaticallyImplyLeading: false,
        leading: widget.showBackButton
            ? IconButton(
                icon: const Icon(FluentIcons.back),
                onPressed: () => Navigator.pop(context),
              )
            : null,
        title: const PageHeader(
          title: Align(
            alignment: AlignmentDirectional.centerStart,
            child: Text('Cài đặt'),
          ),
        ),
        actions: widget.showBackButton
            ? const Row(
                mainAxisAlignment: MainAxisAlignment.end,
                children: [WindowButtons()],
              )
            : null,
      ),
      content: ScaffoldPage.scrollable(
        children: [
          Text('Chế độ giao diện',
              style: FluentTheme.of(context).typography.subtitle),
          description(
            content: const Text(
              'Thay đổi giao diện sáng/tối của ứng dụng. Chọn "Hệ thống" để theo cài đặt của Windows.',
            ),
          ),
          spacer,
          ...List.generate(ThemeMode.values.length, (index) {
            final mode = ThemeMode.values[index];
            return Padding(
              padding: const EdgeInsetsDirectional.only(bottom: 8.0),
              child: RadioButton(
                checked: appTheme.mode == mode,
                onChanged: (value) {
                  if (value) {
                    appTheme.mode = mode;
                  }
                },
                content: Text(
                  '$mode'
                      .replaceAll('ThemeMode.', '')
                      .replaceAll('system', 'Hệ thống')
                      .replaceAll('light', 'Sáng')
                      .replaceAll('dark', 'Tối'),
                ),
              ),
            );
          }),
          biggerSpacer,
          Text('Màu chủ đề',
              style: FluentTheme.of(context).typography.subtitle),
          description(
            content: const Text(
              'Màu sắc chủ đạo được sử dụng trong toàn bộ ứng dụng. Chọn "Hệ thống" để theo màu accent của Windows.',
            ),
          ),
          spacer,
          Wrap(children: [
            Tooltip(
              message: 'Hệ thống',
              child: _buildColorBlock(appTheme, systemAccentColor),
            ),
            ...List.generate(Colors.accentColors.length, (index) {
              final color = Colors.accentColors[index];
              return Tooltip(
                message: accentColorNames[index + 1]
                    .replaceAll('Yellow', 'Vàng')
                    .replaceAll('Orange', 'Cam')
                    .replaceAll('Red', 'Đỏ')
                    .replaceAll('Magenta', 'Hồng')
                    .replaceAll('Purple', 'Tím')
                    .replaceAll('Blue', 'Xanh dương')
                    .replaceAll('Teal', 'Xanh ngọc')
                    .replaceAll('Green', 'Xanh lá'),
                child: _buildColorBlock(appTheme, color),
              );
            }),
          ]),
          biggerSpacer,
          Text('Hướng văn bản',
              style: FluentTheme.of(context).typography.subtitle),
          description(
            content: const Text(
              'Thay đổi hướng hiển thị văn bản từ trái sang phải hoặc ngược lại.',
            ),
          ),
          spacer,
          ...List.generate(TextDirection.values.length, (index) {
            final direction = TextDirection.values[index];
            return Padding(
              padding: const EdgeInsetsDirectional.only(bottom: 8.0),
              child: RadioButton(
                checked: appTheme.textDirection == direction,
                onChanged: (value) {
                  if (value) appTheme.textDirection = direction;
                },
                content: Text(
                  direction == TextDirection.ltr
                      ? 'Trái sang phải'
                      : 'Phải sang trái',
                ),
              ),
            );
          }).reversed,
          if (widget.showDisconnectButton) ...[],
          if (!_showAdvancedSettings) ...[
            biggerSpacer,
            FilledButton(
              child: const Text('Cấu hình nâng cao'),
              onPressed: _showPasswordDialog,
            ),
          ],
          if (_showAdvancedSettings) ...[
            biggerSpacer,
            Text('Cài đặt nâng cao',
                style: FluentTheme.of(context).typography.subtitle),
            description(
              content: const Text('Các cài đặt dành cho quản trị viên.'),
            ),
            spacer,
            _AdvancedConfigEditor(),
          ],
        ],
      ),
    );
  }

  Widget _buildColorBlock(AppTheme appTheme, AccentColor color) {
    return Padding(
      padding: const EdgeInsets.all(2.0),
      child: Button(
        onPressed: () {
          appTheme.color = color;
        },
        style: ButtonStyle(
          padding: const WidgetStatePropertyAll(EdgeInsets.zero),
          backgroundColor: WidgetStateProperty.resolveWith((states) {
            if (states.isPressed) {
              return color.light;
            } else if (states.isHovered) {
              return color.lighter;
            }
            return color;
          }),
        ),
        child: Container(
          height: 40,
          width: 40,
          alignment: AlignmentDirectional.center,
          child: appTheme.color == color
              ? Icon(
                  FluentIcons.check_mark,
                  color: color.basedOnLuminance(),
                  size: 22.0,
                )
              : null,
        ),
      ),
    );
  }
}

class _AdvancedConfigEditor extends StatefulWidget {
  @override
  State<_AdvancedConfigEditor> createState() => _AdvancedConfigEditorState();
}

class _AdvancedConfigEditorState extends State<_AdvancedConfigEditor> {
  final _serverUrlController = TextEditingController();
  final _timeoutController = TextEditingController();
  final _adminPasswordController = TextEditingController();
  bool _loading = true;
  bool _obscureAdminPassword = true;
  String? _pingStatus; // null, 'checking', 'success', 'fail'
  String? _pingMessage;

  @override
  void initState() {
    super.initState();
    _loadConfig();
  }

  Future<void> _loadConfig() async {
    String defaultUrl = 'http://localhost/API';
    int defaultTimeout = 15000;
    try {
      final file = File(configPath);
      if (await file.exists()) {
        final content = await file.readAsString();
        final decoded = utf8.decode(base64.decode(content));
        final config = json.decode(decoded);
        _serverUrlController.text =
            config['server_url']?.toString() ?? defaultUrl;
        _timeoutController.text =
            config['timeout']?.toString() ?? defaultTimeout.toString();
        _adminPasswordController.text =
            config['admin_password']?.toString() ?? '';
      } else {
        _serverUrlController.text = defaultUrl;
        _timeoutController.text = defaultTimeout.toString();
        _adminPasswordController.text = '';
      }
    } catch (_) {
      _serverUrlController.text = defaultUrl;
      _timeoutController.text = defaultTimeout.toString();
      _adminPasswordController.text = '';
    }
    if (mounted) setState(() => _loading = false);
  }

  Future<void> _saveConfig() async {
    final serverUrl = _serverUrlController.text.trim();
    final timeout = int.tryParse(_timeoutController.text.trim()) ?? 15000;
    final adminPassword = _adminPasswordController.text.trim();
    if (serverUrl.isEmpty || adminPassword.isEmpty) {
      showDialog(
        context: context,
        builder: (context) => ContentDialog(
          title: const Text('Thiếu thông tin'),
          content: const Text(
              'Vui lòng nhập đầy đủ địa chỉ máy chủ và mật khẩu quản trị.'),
          actions: [
            Button(
              child: const Text('Đóng'),
              onPressed: () => Navigator.pop(context),
            ),
          ],
        ),
      );
      return;
    }
    final config = {
      'server_url': serverUrl,
      'timeout': timeout,
      'admin_password': adminPassword,
    };
    try {
      final dir = Directory('copecute');
      if (!await dir.exists()) {
        await dir.create(recursive: true);
      }
      final file = File(configPath);
      final encoded = base64.encode(utf8.encode(json.encode(config)));
      await file.writeAsString(encoded, flush: true);
      if (mounted) {
        showDialog(
          context: context,
          builder: (context) => ContentDialog(
            title: const Text('Thành công'),
            content:
                const Text('Đã lưu cấu hình. Vui lòng khởi động lại ứng dụng.'),
            actions: [
              Button(
                child: const Text('Đóng'),
                onPressed: () => Navigator.pop(context),
              ),
            ],
          ),
        );
      }
    } catch (e) {
      if (mounted) {
        showDialog(
          context: context,
          builder: (context) => ContentDialog(
            title: const Text('Lỗi'),
            content: Text('Không thể lưu file config: $e'),
            actions: [
              Button(
                child: const Text('Đóng'),
                onPressed: () => Navigator.pop(context),
              ),
            ],
          ),
        );
      }
    }
  }

  Future<void> _checkServerConnection() async {
    setState(() {
      _pingStatus = 'checking';
      _pingMessage = null;
    });
    final url = _serverUrlController.text.trim();
    if (url.isEmpty) {
      setState(() {
        _pingStatus = 'fail';
        _pingMessage = 'Vui lòng nhập địa chỉ máy chủ.';
      });
      return;
    }
    try {
      final uri =
          Uri.parse(url.endsWith('/') ? url + 'index.php' : url + '/index.php');
      final response = await http.get(uri).timeout(const Duration(seconds: 5));
      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        if (data is Map && data['message'] == 'hello world copecute') {
          setState(() {
            _pingStatus = 'success';
            _pingMessage = 'Kết nối thành công!';
          });
          return;
        }
      }
      setState(() {
        _pingStatus = 'fail';
        _pingMessage = 'Phản hồi không hợp lệ.';
      });
    } catch (e) {
      setState(() {
        _pingStatus = 'fail';
        _pingMessage = 'Lỗi: $e';
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    if (_loading) {
      return const Center(child: ProgressRing());
    }
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        const Text('Địa chỉ máy chủ:'),
        const SizedBox(height: 8),
        Row(
          children: [
            Expanded(
              child: TextBox(
                controller: _serverUrlController,
                placeholder: 'Nhập địa chỉ máy chủ',
              ),
            ),
            const SizedBox(width: 8),
            FilledButton(
              child: const Text('Kiểm tra kết nối'),
              onPressed: _checkServerConnection,
            ),
          ],
        ),
        if (_pingStatus == 'checking') ...[
          SizedBox(height: 8),
          ProgressRing(),
          SizedBox(height: 4),
          Text('Đang kiểm tra kết nối...'),
        ] else if (_pingStatus == 'success') ...[
          SizedBox(height: 8),
          Icon(FluentIcons.accept, color: Colors.green),
          SizedBox(height: 4),
          Text(_pingMessage ?? 'Kết nối thành công!',
              style: TextStyle(color: Colors.green)),
        ] else if (_pingStatus == 'fail') ...[
          SizedBox(height: 8),
          Icon(FluentIcons.error, color: Colors.red),
          SizedBox(height: 4),
          Text(_pingMessage ?? 'Kết nối thất bại!',
              style: TextStyle(color: Colors.red)),
        ],
        const SizedBox(height: 16),
        const Text('Timeout (ms):'),
        const SizedBox(height: 8),
        TextBox(
          controller: _timeoutController,
          placeholder: 'Nhập timeout (ms)',
          keyboardType: TextInputType.number,
        ),
        const SizedBox(height: 16),
        const Text('Mật khẩu cấu hình:'),
        const SizedBox(height: 8),
        TextBox(
          controller: _adminPasswordController,
          placeholder: 'Nhập mật khẩu quản trị',
          obscureText: _obscureAdminPassword,
          suffix: IconButton(
            icon: Icon(
                _obscureAdminPassword ? FluentIcons.hide : FluentIcons.red_eye),
            onPressed: () {
              setState(() => _obscureAdminPassword = !_obscureAdminPassword);
            },
          ),
        ),
        const SizedBox(height: 16),
        FilledButton(
          child: const Text('Lưu cấu hình'),
          onPressed: _saveConfig,
        ),
      ],
    );
  }

  @override
  void dispose() {
    _serverUrlController.dispose();
    _timeoutController.dispose();
    _adminPasswordController.dispose();
    super.dispose();
  }
}
