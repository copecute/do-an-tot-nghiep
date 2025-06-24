import 'package:flutter/foundation.dart';
import 'package:flutter/services.dart';
import 'package:shared_preferences/shared_preferences.dart';

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
  static const String ADMIN_PASSWORD = 'copecute123';

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
            onPressed: () {
              if (_passwordController.text == ADMIN_PASSWORD) {
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

  Future<void> _updateMaySo() async {
    final prefs = await SharedPreferences.getInstance();
    final currentMaySo = prefs.getString('may_so') ?? '';

    final controller = TextEditingController(text: currentMaySo);

    showDialog(
      context: context,
      builder: (context) => ContentDialog(
        title: const Text('Cập nhật số máy'),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text('Số máy hiện tại: '),
            const SizedBox(height: 8),
            TextBox(
              controller: controller,
              placeholder: 'Nhập số máy mới (1-99)',
              keyboardType: TextInputType.number,
              inputFormatters: [
                FilteringTextInputFormatter.digitsOnly,
                LengthLimitingTextInputFormatter(2),
                TextInputFormatter.withFunction((oldValue, newValue) {
                  // kiểm tra giá trị nhập vào phải từ 1-99
                  if (newValue.text.isEmpty) return newValue;
                  final n = int.tryParse(newValue.text);
                  if (n == null || n < 1 || n > 99) return oldValue;
                  return newValue;
                }),
              ],
            ),
          ],
        ),
        actions: [
          FilledButton(
            child: const Text('Cập nhật'),
            onPressed: () async {
              if (controller.text.isNotEmpty) {
                await prefs.setString('may_so', controller.text);
                if (mounted) {
                  Navigator.pop(context);
                  showDialog(
                    context: context,
                    builder: (context) => ContentDialog(
                      title: const Text('Thành công'),
                      content: const Text(
                          'Đã cập nhật số máy. Vui lòng khởi động lại ứng dụng.'),
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
            },
          ),
          Button(
            child: const Text('Hủy'),
            onPressed: () => Navigator.pop(context),
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

  Future<void> _disconnectServer() async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove('server_url');
    await prefs.remove('user_token');
    await prefs.remove('user_id');
    await prefs.remove('username');
    await prefs.remove('email');
    await prefs.remove('user_role');
    print('🔌 Đã ngắt kết nối và xóa thông tin server/người dùng');

    if (!mounted) return;

    Navigator.pushAndRemoveUntil(
      context,
      FluentPageRoute(builder: (context) => const SplashScreen()),
      (route) => false,
    );
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
          if (widget.showDisconnectButton) ...[
            biggerSpacer,
            Text('Kết nối máy chủ',
                style: FluentTheme.of(context).typography.subtitle),
            description(
              content: const Text(
                'Ngắt kết nối với máy chủ nhằm cập nhật địa chỉ máy chủ. Bạn sẽ cần đăng nhập lại.',
              ),
            ),
            spacer,
            FilledButton(
              style: ButtonStyle(
                backgroundColor: ButtonState.resolveWith((states) {
                  if (states.isPressed) {
                    return Colors.red;
                  }
                  return Colors.red;
                }),
              ),
              onPressed: () {
                showDialog(
                  context: context,
                  builder: (context) => ContentDialog(
                    title: const Text('Xác nhận ngắt kết nối'),
                    content: const Text(
                        'Bạn có chắc chắn muốn ngắt kết nối khỏi máy chủ? Ứng dụng sẽ quay về màn hình kết nối.'),
                    actions: [
                      FilledButton(
                        child: const Text('Có'),
                        onPressed: () {
                          Navigator.pop(context);
                          _disconnectServer();
                        },
                      ),
                      Button(
                        child: const Text('Không'),
                        onPressed: () => Navigator.pop(context),
                      ),
                    ],
                  ),
                );
              },
              child: const Row(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Icon(FluentIcons.plug_disconnected),
                  SizedBox(width: 8),
                  Text('Ngắt kết nối máy chủ'),
                ],
              ),
            ),
          ],
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
            FilledButton(
              child: const Text('Sửa số máy'),
              onPressed: _updateMaySo,
            ),
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
