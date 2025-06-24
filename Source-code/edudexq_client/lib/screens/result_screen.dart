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
              child: Text('Kết quả bài làm',
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
              color: Colors.white,
              borderRadius: BorderRadius.circular(16),
              boxShadow: [
                BoxShadow(
                  color: Colors.black.withOpacity(0.08),
                  blurRadius: 20,
                  offset: const Offset(0, 8),
                ),
              ],
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
                  const SizedBox(height: 16),
                  Text('Kết quả bài thi',
                      style: FluentTheme.of(context)
                          .typography
                          .titleLarge
                          ?.copyWith(fontSize: 26)),
                  const SizedBox(height: 8),
                  Text('Kỳ thi: ${kyThi!['tenKyThi']} - ${kyThi!['monHoc']}',
                      style: const TextStyle(color: Colors.grey, fontSize: 16)),
                  const SizedBox(height: 24),
                  Row(
                    children: [
                      Expanded(
                        child: Card(
                          backgroundColor: Colors.blue.lightest,
                          child: Padding(
                            padding: const EdgeInsets.all(18),
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Row(
                                  children: [
                                    Icon(FluentIcons.contact,
                                        color: Colors.blue, size: 24),
                                    const SizedBox(width: 8),
                                    const Text('Thông tin thí sinh',
                                        style: TextStyle(
                                            fontWeight: FontWeight.bold,
                                            fontSize: 16)),
                                  ],
                                ),
                                const SizedBox(height: 8),
                                Text('Họ tên: ${thiSinh!['hoTen']}',
                                    style: const TextStyle(fontSize: 15)),
                                Text('Mã sinh viên: ${thiSinh!['maSinhVien']}',
                                    style: const TextStyle(fontSize: 15)),
                                Text('Số báo danh: ${thiSinh!['soBaoDanh']}',
                                    style: const TextStyle(fontSize: 15)),
                              ],
                            ),
                          ),
                        ),
                      ),
                      const SizedBox(width: 18),
                      Expanded(
                        child: Card(
                          backgroundColor: Colors.orange.lightest,
                          child: Padding(
                            padding: const EdgeInsets.all(18),
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Row(
                                  children: [
                                    Icon(FluentIcons.task_logo,
                                        color: Colors.orange, size: 24),
                                    const SizedBox(width: 8),
                                    const Text('Thông tin bài thi',
                                        style: TextStyle(
                                            fontWeight: FontWeight.bold,
                                            fontSize: 16)),
                                  ],
                                ),
                                const SizedBox(height: 8),
                                Text('Tên đề thi: ${ketQua!['tenDeThi'] ?? ''}',
                                    style: const TextStyle(fontSize: 15)),
                                Text(
                                    'Thời gian nộp: ${ketQua!['thoiGianNop'] ?? ''}',
                                    style: const TextStyle(fontSize: 15)),
                                if (ketQua!['soCauDung'] != null &&
                                    ketQua!['tongSoCau'] != null)
                                  Text(
                                      'Số câu đúng: ${ketQua!['soCauDung']}/${ketQua!['tongSoCau']}',
                                      style: const TextStyle(fontSize: 15)),
                              ],
                            ),
                          ),
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 28),
                  Card(
                    backgroundColor:
                        isPassed ? Colors.green.lightest : Colors.red.lightest,
                    child: Padding(
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
                          Text(
                              '${ketQua!['diem']?.toStringAsFixed(2) ?? 'N/A'}',
                              style: TextStyle(
                                  fontSize: 56,
                                  fontWeight: FontWeight.bold,
                                  color: isPassed ? Colors.green : Colors.red)),
                          const Text('điểm', style: TextStyle(fontSize: 18)),
                        ],
                      ),
                    ),
                  ),
                  const SizedBox(height: 32),
                  FilledButton(
                    style: ButtonStyle(
                      padding: ButtonState.all(const EdgeInsets.symmetric(
                          vertical: 18, horizontal: 40)),
                      backgroundColor: ButtonState.all(Colors.blue),
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
                    child: Text('Đăng xuất', style: TextStyle(fontSize: 18)),
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
