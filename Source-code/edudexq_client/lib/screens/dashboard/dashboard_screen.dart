import 'package:fluent_ui/fluent_ui.dart' hide Page;
import 'package:flutter/foundation.dart';
import 'package:provider/provider.dart';
import 'package:window_manager/window_manager.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:http/http.dart' as http;
import 'dart:convert';

import '../../theme.dart';
import 'home_page.dart';
import '../settings.dart';
import '../login.dart';

class DashboardScreen extends StatefulWidget {
  final int? initialPage;
  final String? fileToOpen;

  const DashboardScreen({
    super.key,
    this.initialPage,
    this.fileToOpen,
  });

  @override
  State<DashboardScreen> createState() => _DashboardScreenState();
}

class _DashboardScreenState extends State<DashboardScreen> with WindowListener {
  bool value = false;
  final viewKey = GlobalKey(debugLabel: 'dashboard_view_key');
  int _currentIndex = 0;
  late final List<Widget> _pages;

  @override
  void initState() {
    windowManager.addListener(this);
    super.initState();
    _pages = [
      const HomePage(),
      const Settings(),
    ];
    if (widget.initialPage != null) {
      _currentIndex = widget.initialPage!;
      if (_currentIndex > 1) _currentIndex = 1;
    }
  }

  @override
  void dispose() {
    windowManager.removeListener(this);
    super.dispose();
  }

  Future<void> _logout() async {
    try {
      // xoá thông tin đăng nhập đã lưu
      final prefs = await SharedPreferences.getInstance();
      await prefs.remove('token'); // xoá token đã lưu
      await prefs.remove('student_data'); // xoá thông tin sinh viên đã lưu

      if (mounted) {
        // chuyển về màn hình đăng nhập
        Navigator.of(context).pushAndRemoveUntil(
          FluentPageRoute(builder: (context) => const LoginScreen()),
          (route) => false,
        );
      }
    } catch (e) {
      if (mounted) {
        showDialog(
          context: context,
          builder: (context) => ContentDialog(
            title: const Text('Lỗi'),
            content: Text('Đã có lỗi xảy ra: e.toString()'),
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

  @override
  Widget build(BuildContext context) {
    final appTheme = context.watch<AppTheme>();

    return NavigationView(
      key: viewKey,
      appBar: NavigationAppBar(
        automaticallyImplyLeading: false,
        title: const DragToMoveArea(
          child: Align(
            alignment: AlignmentDirectional.centerStart,
            child: Text('EduDex Quiz'),
          ),
        ),
        actions: Row(
          mainAxisAlignment: MainAxisAlignment.end,
          children: [
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
      pane: NavigationPane(
        selected: _currentIndex,
        onChanged: (index) => setState(() => _currentIndex = index),
        items: [
          PaneItem(
            icon: const Icon(FluentIcons.home),
            title: const Text('Tổng quan'),
            body: _pages[0],
          ),
          // Đã xoá lịch sử
        ],
        footerItems: [
          PaneItemSeparator(),
          PaneItem(
            icon: const Icon(FluentIcons.settings),
            title: const Text('Cài đặt'),
            body: _pages[1],
          ),
          PaneItem(
            icon: const Icon(FluentIcons.sign_out),
            title: const Text('Đăng xuất'),
            body: _pages[0],
            onTap: () {
              showDialog(
                context: context,
                builder: (context) => ContentDialog(
                  title: const Text('Xác nhận'),
                  content: const Text('Bạn có chắc chắn muốn đăng xuất?'),
                  actions: [
                    Button(
                      child: const Text('Không'),
                      onPressed: () => Navigator.pop(context),
                    ),
                    FilledButton(
                      child: const Text('Có'),
                      onPressed: () {
                        Navigator.pop(context);
                        _logout();
                      },
                    ),
                  ],
                ),
              );
            },
          ),
        ],
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
