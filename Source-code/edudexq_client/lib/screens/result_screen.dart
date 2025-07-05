import 'dart:convert';
import 'dart:io';
import 'package:fluent_ui/fluent_ui.dart';
import 'package:window_manager/window_manager.dart';
import 'package:provider/provider.dart';
import '../theme.dart';
import 'package:edudex_quiz_client/screens/dashboard/dashboard_screen.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:intl/intl.dart';
import 'dart:developer';
import 'package:process_run/process_run.dart';

class ResultScreen extends StatefulWidget {
  const ResultScreen({super.key});

  @override
  State<ResultScreen> createState() => _ResultScreenState();
}

class _ResultScreenState extends State<ResultScreen> {
  Map<String, dynamic>? thiSinh;
  Map<String, dynamic>? kyThi;
  Map<String, dynamic>? deThi;
  Map<String, dynamic>? ketQua;

  @override
  void initState() {
    super.initState();
    _loadData();
  }

  Future<void> _loadData() async {
    final prefs = await SharedPreferences.getInstance();
    final thiSinhStr = prefs.getString('thiSinh');
    final kyThiStr = prefs.getString('kyThi');
    final ketQuaStr = prefs.getString('ketQua');
    if (thiSinhStr == null || kyThiStr == null || ketQuaStr == null) {
      if (mounted) {
        Navigator.pushReplacementNamed(context, '/login');
      }
      return;
    }
    setState(() {
      thiSinh = json.decode(thiSinhStr);
      kyThi = json.decode(kyThiStr);
      ketQua = json.decode(ketQuaStr);
    });
  }

  @override
  Widget build(BuildContext context) {
    final appTheme = context.watch<AppTheme>();
    final theme = FluentTheme.of(context);
    final isDark = theme.brightness == Brightness.dark;
    final cardColor = isDark ? Colors.grey[180] : Colors.white;
    final borderRadius = BorderRadius.circular(18);
    final cardShadow = [
      BoxShadow(
        color: isDark
            ? Colors.black.withOpacity(0.10)
            : Colors.black.withOpacity(0.05),
        blurRadius: 16,
        offset: const Offset(0, 4),
      ),
    ];
    final textColor = isDark ? Colors.white : Colors.black;
    final subTextColor = isDark ? Colors.grey[120] : Colors.grey[130];
    if (thiSinh == null || kyThi == null || ketQua == null) {
      return const Center(child: ProgressRing());
    }
    final isPassed = (ketQua!['diem'] ?? 0) >= 5.0;
    return NavigationView(
      appBar: NavigationAppBar(
        automaticallyImplyLeading: false,
        title: () {
          return const DragToMoveArea(
            child: Align(
              alignment: AlignmentDirectional.centerStart,
              child: Text('',
                  style: TextStyle(fontSize: 28, fontWeight: FontWeight.bold)),
            ),
          );
        }(),
      ),
      content: ScaffoldPage(
        padding: EdgeInsets.zero,
        content: Center(
          child: Container(
            constraints: const BoxConstraints(maxWidth: 700),
            padding: const EdgeInsets.all(32),
            decoration: BoxDecoration(
              color: isDark ? Colors.grey[200] : Colors.white,
              borderRadius: BorderRadius.circular(20),
              boxShadow: cardShadow,
            ),
            child: SingleChildScrollView(
              child: Column(
                mainAxisSize: MainAxisSize.min,
                crossAxisAlignment: CrossAxisAlignment.center,
                children: [
                  Row(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      Icon(
                        isPassed ? FluentIcons.completed : FluentIcons.error,
                        color: isPassed ? Colors.green : Colors.red,
                        size: 40,
                      ),
                      const SizedBox(width: 12),
                      Text(
                        isPassed ? 'ĐẠT' : 'KHÔNG ĐẠT',
                        style: TextStyle(
                          fontSize: 32,
                          fontWeight: FontWeight.bold,
                          color: isPassed ? Colors.green : Colors.red,
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 12),
                  Text('Kết quả bài thi',
                      style: theme.typography.titleLarge
                          ?.copyWith(fontSize: 26, color: textColor)),
                  const SizedBox(height: 6),
                  Text('Kỳ thi: ${kyThi!['tenKyThi']} - ${kyThi!['monHoc']}',
                      style: TextStyle(color: subTextColor, fontSize: 16)),
                  const SizedBox(height: 24),
                  Row(
                    children: [
                      Expanded(
                        child: Container(
                          decoration: BoxDecoration(
                            color: cardColor,
                            borderRadius: borderRadius,
                            boxShadow: cardShadow,
                          ),
                          padding: const EdgeInsets.all(18),
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Row(
                                children: [
                                  Icon(FluentIcons.contact,
                                      color: Colors.blue, size: 24),
                                  const SizedBox(width: 8),
                                  Text('Thông tin thí sinh',
                                      style: TextStyle(
                                          fontWeight: FontWeight.bold,
                                          fontSize: 16,
                                          color: Colors.blue)),
                                ],
                              ),
                              const SizedBox(height: 8),
                              Text('Họ tên: ${thiSinh!['hoTen']}',
                                  style: TextStyle(
                                      fontSize: 15, color: textColor)),
                              Text('Mã sinh viên: ${thiSinh!['maSinhVien']}',
                                  style: TextStyle(
                                      fontSize: 15, color: textColor)),
                              Text('Số báo danh: ${thiSinh!['soBaoDanh']}',
                                  style: TextStyle(
                                      fontSize: 15, color: textColor)),
                            ],
                          ),
                        ),
                      ),
                      const SizedBox(width: 18),
                      Expanded(
                        child: Container(
                          decoration: BoxDecoration(
                            color: cardColor,
                            borderRadius: borderRadius,
                            boxShadow: cardShadow,
                          ),
                          padding: const EdgeInsets.all(18),
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Row(
                                children: [
                                  Icon(FluentIcons.task_logo,
                                      color: Colors.orange, size: 24),
                                  const SizedBox(width: 8),
                                  Text('Thông tin bài thi',
                                      style: TextStyle(
                                          fontWeight: FontWeight.bold,
                                          fontSize: 16,
                                          color: Colors.orange)),
                                ],
                              ),
                              const SizedBox(height: 8),
                              Text('Tên đề thi: ${ketQua!['tenDeThi'] ?? ''}',
                                  style: TextStyle(
                                      fontSize: 15, color: textColor)),
                              Text(
                                  'Thời gian nộp: ${ketQua!['thoiGianNop'] ?? ''}',
                                  style: TextStyle(
                                      fontSize: 15, color: textColor)),
                              if (ketQua!['soCauDung'] != null &&
                                  ketQua!['tongSoCau'] != null)
                                Text(
                                    'Số câu đúng: ${ketQua!['soCauDung']}/${ketQua!['tongSoCau']}',
                                    style: TextStyle(
                                        fontSize: 15, color: textColor)),
                            ],
                          ),
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 28),
                  Container(
                    width: 260,
                    decoration: BoxDecoration(
                      color: isPassed
                          ? Colors.green.lightest
                          : Colors.red.lightest,
                      borderRadius: borderRadius,
                      boxShadow: cardShadow,
                    ),
                    padding: const EdgeInsets.symmetric(
                        vertical: 32, horizontal: 24),
                    child: Column(
                      children: [
                        Text('Điểm số',
                            style: TextStyle(
                                fontSize: 20,
                                fontWeight: FontWeight.bold,
                                color: isPassed ? Colors.green : Colors.red)),
                        const SizedBox(height: 10),
                        Text('${ketQua!['diem']?.toStringAsFixed(2) ?? 'N/A'}',
                            style: TextStyle(
                                fontSize: 56,
                                fontWeight: FontWeight.bold,
                                color: isPassed ? Colors.green : Colors.red)),
                        const Text('điểm', style: TextStyle(fontSize: 18)),
                      ],
                    ),
                  ),
                  const SizedBox(height: 32),
                  FilledButton(
                    style: ButtonStyle(
                      padding: ButtonState.all(const EdgeInsets.symmetric(
                          vertical: 18, horizontal: 40)),
                      backgroundColor: ButtonState.all(Colors.blue),
                      shape: ButtonState.all(
                          RoundedRectangleBorder(borderRadius: borderRadius)),
                    ),
                    onPressed: () async {
                      final prefs = await SharedPreferences.getInstance();
                      await prefs.remove('thiSinh');
                      await prefs.remove('kyThi');
                      await prefs.remove('deThi');
                      await prefs.remove('ketQua');
                      if (mounted) {
                        Navigator.pushReplacementNamed(context, '/login');
                      }
                    },
                    child: Text('Đăng xuất',
                        style: TextStyle(fontSize: 18, color: Colors.white)),
                  ),
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }
}
