import 'package:fluent_ui/fluent_ui.dart';
import 'package:flutter_svg/flutter_svg.dart';
import 'package:window_manager/window_manager.dart';
import 'package:provider/provider.dart';
import 'package:edudex_quiz_client/theme.dart';
import 'package:edudex_quiz_client/screens/dashboard/dashboard_screen.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:http/http.dart' as http;
import 'dart:convert';
import 'settings.dart';

class LoginScreen extends StatefulWidget {
  const LoginScreen({super.key});

  @override
  State<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends State<LoginScreen> with WindowListener {
  final _soBaoDanhController = TextEditingController();
  final _maSinhVienController = TextEditingController();
  final _formKey = GlobalKey<FormState>();
  final _soBaoDanhFocusNode = FocusNode();
  final _maSinhVienFocusNode = FocusNode();
  final _loginFocusNode = FocusNode();
  String? _errorMessage;
  bool _rememberMe = false;
  bool _isLoading = false;

  @override
  void initState() {
    windowManager.addListener(this);
    super.initState();
  }

  @override
  void dispose() {
    windowManager.removeListener(this);
    _soBaoDanhController.dispose();
    _maSinhVienController.dispose();
    if (_soBaoDanhFocusNode.hasListeners) {
      _soBaoDanhFocusNode.dispose();
    }
    if (_maSinhVienFocusNode.hasListeners) {
      _maSinhVienFocusNode.dispose();
    }
    if (_loginFocusNode.hasListeners) {
      _loginFocusNode.dispose();
    }
    super.dispose();
  }

  Future<void> _login() async {
    if (_soBaoDanhController.text.isEmpty ||
        _maSinhVienController.text.isEmpty) {
      setState(() {
        _errorMessage = 'Vui lòng nhập đầy đủ thông tin!';
      });
      return;
    }

    setState(() {
      _isLoading = true;
      _errorMessage = null;
    });

    try {
      final prefs = await SharedPreferences.getInstance();
      final serverUrl = prefs.getString('server_url');
      if (serverUrl == null) {
        throw Exception('Không tìm thấy địa chỉ máy chủ');
      }
      final apiUrl = serverUrl.endsWith('/')
          ? '${serverUrl}xac-thuc-thi-sinh/index.php'
          : '$serverUrl/xac-thuc-thi-sinh/index.php';

      final response = await http.post(
        Uri.parse(apiUrl),
        headers: {
          'Content-Type': 'application/json',
        },
        body: json.encode({
          'maSinhVien': _maSinhVienController.text,
          'soBaoDanh': _soBaoDanhController.text,
        }),
      );

      final data = json.decode(response.body);

      if (response.statusCode == 200 &&
          data['success'] == true &&
          data['message'] == 'Xác thực thành công') {
        // Lưu thông tin vào SharedPreferences
        await prefs.setString('thiSinh', json.encode(data['data']['thiSinh']));
        await prefs.setString('kyThi', json.encode(data['data']['kyThi']));
        await prefs.setString('deThi', json.encode(data['data']['deThi']));
        if (data['data']['deThi'] != null &&
            data['data']['deThi']['daLamBai'] == true) {
          // Nếu đã làm bài, lưu deThi vào ketQua và chuyển sang trang kết quả
          await prefs.setString('ketQua', json.encode(data['data']['deThi']));
          if (mounted) {
            Navigator.pushReplacementNamed(context, '/ket-qua');
          }
        } else {
          // Nếu chưa làm bài, chuyển sang trang home
          if (mounted) {
            Navigator.pushReplacementNamed(context, '/home');
          }
        }
      } else {
        setState(() {
          _errorMessage = data['message'] ?? 'Đăng nhập thất bại';
        });
      }
    } catch (e, stack) {
      print('Lỗi đăng nhập: $e');
      print(stack);
      setState(() {
        _errorMessage = 'Lỗi: ${e.toString()}';
      });
    } finally {
      if (mounted) {
        setState(() {
          _isLoading = false;
        });
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final appTheme = context.watch<AppTheme>();
    final size = MediaQuery.of(context).size;
    final isSmallScreen = size.width < 900;

    Widget buildLoginForm() {
      return Form(
        key: _formKey,
        child: Container(
          padding: const EdgeInsets.all(48.0),
          constraints: const BoxConstraints(maxWidth: 500),
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            crossAxisAlignment: CrossAxisAlignment.center,
            children: [
              Center(
                child: Image.asset(
                  'assets/logo.png',
                  width: 120,
                  height: 120,
                ),
              ),
              const SizedBox(height: 32),
              Center(
                child: Text(
                  'Đăng nhập',
                  style: FluentTheme.of(context).typography.titleLarge,
                ),
              ),
              const SizedBox(height: 32),
              InfoLabel(
                label: 'Số báo danh',
                child: TextBox(
                  controller: _soBaoDanhController,
                  placeholder: 'Nhập số báo danh',
                  focusNode: _soBaoDanhFocusNode,
                  onSubmitted: (_) => _maSinhVienFocusNode.requestFocus(),
                ),
              ),
              const SizedBox(height: 16),
              InfoLabel(
                label: 'Mã sinh viên',
                child: TextBox(
                  controller: _maSinhVienController,
                  placeholder: 'Nhập mã sinh viên',
                  focusNode: _maSinhVienFocusNode,
                  onSubmitted: (_) => _login(),
                ),
              ),
              if (_errorMessage != null) ...[
                const SizedBox(height: 24),
                Text(
                  _errorMessage!,
                  style: TextStyle(color: Colors.errorPrimaryColor),
                ),
              ],
              const SizedBox(height: 24),
              SizedBox(
                width: double.infinity,
                child: FilledButton(
                  focusNode: _loginFocusNode,
                  onPressed: _isLoading ? null : _login,
                  child: Padding(
                    padding: const EdgeInsets.all(8.0),
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
                              Text('Đang đăng nhập...'),
                            ],
                          )
                        : const Text('Kiểm tra thông tin'),
                  ),
                ),
              ),
            ],
          ),
        ),
      );
    }

    Widget buildIllustration() {
      if (isSmallScreen) return const SizedBox.shrink();

      return Container(
        constraints: const BoxConstraints(maxWidth: 600),
        child: Center(
          child: SvgPicture.asset(
            'assets/login_illustration.svg',
            width: 500,
          ),
        ),
      );
    }

    return NavigationView(
      appBar: NavigationAppBar(
        automaticallyImplyLeading: false,
        title: () {
          return const DragToMoveArea(
            child: Align(
              alignment: AlignmentDirectional.centerStart,
              // child: Text('Đăng nhập'),
            ),
          );
        }(),
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
                    if (v) {
                      appTheme.mode = ThemeMode.dark;
                    } else {
                      appTheme.mode = ThemeMode.light;
                    }
                  },
                ),
              ),
            ),
            const WindowButtons(),
          ],
        ),
      ),
      content: Padding(
        padding: const EdgeInsets.symmetric(horizontal: 16.0),
        child: isSmallScreen
            ? SingleChildScrollView(
                child: Column(
                  children: [
                    const SizedBox(height: 32),
                    buildIllustration(),
                    buildLoginForm(),
                  ],
                ),
              )
            : Row(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  Expanded(
                    child: buildIllustration(),
                  ),
                  Expanded(
                    child: buildLoginForm(),
                  ),
                ],
              ),
      ),
    );
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
    final FluentThemeData theme = FluentTheme.of(context);

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
