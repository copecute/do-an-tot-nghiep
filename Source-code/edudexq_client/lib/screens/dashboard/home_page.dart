import 'package:fluent_ui/fluent_ui.dart';
import 'package:edudex_quiz_client/screens/quiz_screen.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'dart:convert';
import 'package:http/http.dart' as http;
import 'package:intl/intl.dart';
import '../login.dart';

class HomePage extends StatefulWidget {
  const HomePage({super.key});

  @override
  State<HomePage> createState() => _HomePageState();
}

class _HomePageState extends State<HomePage> {
  Map<String, dynamic>? thiSinh;
  Map<String, dynamic>? kyThi;
  Map<String, dynamic>? deThi;
  Duration? timeLeft;
  late final ValueNotifier<DateTime> _now;

  @override
  void initState() {
    super.initState();
    _now = ValueNotifier(DateTime.now());
    _loadData();
    // Cập nhật thời gian mỗi giây
    Future.doWhile(() async {
      await Future.delayed(const Duration(seconds: 1));
      if (mounted) _now.value = DateTime.now();
      return mounted;
    });
  }

  Future<void> _loadData() async {
    final prefs = await SharedPreferences.getInstance();
    final thiSinhStr = prefs.getString('thiSinh');
    final kyThiStr = prefs.getString('kyThi');
    final deThiStr = prefs.getString('deThi');
    if (thiSinhStr == null || kyThiStr == null || deThiStr == null) {
      if (mounted) {
        Navigator.pushReplacement(
          context,
          FluentPageRoute(builder: (context) => const LoginScreen()),
        );
      }
      return;
    }
    final deThiObj = json.decode(deThiStr);
    // Nếu đã làm bài thì chuyển sang trang kết quả
    if (deThiObj['daLamBai'] == true) {
      if (mounted) {
        Navigator.pushReplacementNamed(context, '/ket-qua');
      }
      return;
    }
    setState(() {
      thiSinh = json.decode(thiSinhStr);
      kyThi = json.decode(kyThiStr);
      deThi = deThiObj;
    });
  }

  String _formatDateTime(String dateTimeStr) {
    final date = DateTime.parse(dateTimeStr);
    return DateFormat('dd/MM/yyyy HH:mm:ss').format(date);
  }

  String _countdownText() {
    if (kyThi == null) return '';
    final now = DateTime.now();
    final ketThuc = DateTime.parse(kyThi!['thoiGianKetThuc']);
    final diff = ketThuc.difference(now);
    if (diff.isNegative) return 'Kỳ thi đã kết thúc';
    final h = diff.inHours.remainder(24).toString().padLeft(2, '0');
    final m = diff.inMinutes.remainder(60).toString().padLeft(2, '0');
    final s = diff.inSeconds.remainder(60).toString().padLeft(2, '0');
    return 'Thời gian còn lại: $h:$m:$s';
  }

  Future<void> _logout() async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove('thiSinh');
    await prefs.remove('kyThi');
    await prefs.remove('deThi');
    if (mounted) {
      Navigator.pushAndRemoveUntil(
        context,
        FluentPageRoute(builder: (context) => const LoginScreen()),
        (route) => false,
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    if (thiSinh == null || kyThi == null || deThi == null) {
      return const Center(child: ProgressRing());
    }
    return Center(
      child: Container(
        constraints: const BoxConstraints(maxWidth: 900),
        padding: const EdgeInsets.all(32),
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(12),
          boxShadow: [
            BoxShadow(
              color: Colors.black.withOpacity(0.07),
              blurRadius: 18,
              offset: const Offset(0, 6),
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
                  Icon(FluentIcons.education, size: 36, color: Colors.blue),
                  const SizedBox(width: 12),
                  Text('Hệ thống thi trắc nghiệm',
                      style: FluentTheme.of(context)
                          .typography
                          .titleLarge
                          ?.copyWith(fontSize: 32)),
                ],
              ),
              const SizedBox(height: 8),
              Text('Chào mừng bạn đến với hệ thống thi trực tuyến EduDexQ!',
                  style: TextStyle(color: Colors.grey[120], fontSize: 16)),
              const SizedBox(height: 20),
              InfoBar(
                title: const Text('Lưu ý'),
                content: const Text(
                    'Vui lòng kiểm tra thông tin cá nhân và đề thi trước khi bắt đầu.'),
                severity: InfoBarSeverity.info,
                isLong: true,
              ),
              const SizedBox(height: 24),
              Row(
                children: [
                  Expanded(
                    child: Card(
                      backgroundColor: Colors.blue.lightest,
                      child: Padding(
                        padding: const EdgeInsets.all(20),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Row(
                              children: [
                                Icon(FluentIcons.contact,
                                    color: Colors.blue, size: 28),
                                const SizedBox(width: 8),
                                const Text('Thông tin thí sinh',
                                    style: TextStyle(
                                        fontWeight: FontWeight.bold,
                                        fontSize: 18)),
                              ],
                            ),
                            const SizedBox(height: 10),
                            Text('Họ tên: ${thiSinh!['hoTen']}',
                                style: const TextStyle(fontSize: 16)),
                            Text('Mã sinh viên: ${thiSinh!['maSinhVien']}',
                                style: const TextStyle(fontSize: 16)),
                            Text('Số báo danh: ${thiSinh!['soBaoDanh']}',
                                style: const TextStyle(fontSize: 16)),
                          ],
                        ),
                      ),
                    ),
                  ),
                  const SizedBox(width: 24),
                  Expanded(
                    child: Card(
                      backgroundColor: Colors.orange.lightest,
                      child: Padding(
                        padding: const EdgeInsets.all(20),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Row(
                              children: [
                                Icon(FluentIcons.task_logo,
                                    color: Colors.orange, size: 28),
                                const SizedBox(width: 8),
                                const Text('Thông tin đề thi',
                                    style: TextStyle(
                                        fontWeight: FontWeight.bold,
                                        fontSize: 18)),
                              ],
                            ),
                            const SizedBox(height: 10),
                            Text('Tên đề thi: ${deThi!['tenDeThi']}',
                                style: const TextStyle(fontSize: 16)),
                            Text('Số câu hỏi: ${deThi!['soCau']}',
                                style: const TextStyle(fontSize: 16)),
                            Text(
                                'Thời gian làm bài: ${deThi!['thoiGianLamBai']} phút',
                                style: const TextStyle(fontSize: 16)),
                          ],
                        ),
                      ),
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 24),
              Card(
                backgroundColor: Colors.green.lightest,
                child: Padding(
                  padding: const EdgeInsets.all(20),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Row(
                        children: [
                          Icon(FluentIcons.clock,
                              color: Colors.green, size: 28),
                          const SizedBox(width: 8),
                          const Text('Thời gian thi',
                              style: TextStyle(
                                  fontWeight: FontWeight.bold, fontSize: 18)),
                        ],
                      ),
                      const SizedBox(height: 10),
                      Text(
                          'Thời gian bắt đầu: ${_formatDateTime(kyThi!['thoiGianBatDau'])}',
                          style: const TextStyle(fontSize: 16)),
                      Text(
                          'Thời gian kết thúc: ${_formatDateTime(kyThi!['thoiGianKetThuc'])}',
                          style: const TextStyle(fontSize: 16)),
                      const SizedBox(height: 8),
                      ValueListenableBuilder<DateTime>(
                        valueListenable: _now,
                        builder: (context, value, _) {
                          return Text(_countdownText(),
                              style: TextStyle(
                                  fontSize: 22,
                                  fontWeight: FontWeight.bold,
                                  color: Colors.green));
                        },
                      ),
                    ],
                  ),
                ),
              ),
              const SizedBox(height: 32),
              Row(
                children: [
                  Expanded(
                    child: FilledButton(
                      style: ButtonStyle(
                        padding: ButtonState.all(
                            const EdgeInsets.symmetric(vertical: 18)),
                        backgroundColor: ButtonState.all(Colors.blue),
                      ),
                      onPressed: () {
                        Navigator.pushReplacement(
                          context,
                          FluentPageRoute(
                            builder: (context) => QuizScreen(
                              examInfo: {
                                'subject': {
                                  'name': kyThi!['monHoc'],
                                },
                                'exam': {
                                  'name': deThi!['tenDeThi'],
                                  'duration': deThi!['thoiGianLamBai'],
                                  'total_questions': deThi!['soCau'],
                                },
                                'shift': {
                                  'name': '',
                                  'start_time': kyThi!['thoiGianBatDau'],
                                  'end_time': kyThi!['thoiGianKetThuc'],
                                  'duration': deThi!['thoiGianLamBai'],
                                },
                                'room': {
                                  'name': '',
                                  'facility': '',
                                },
                                'test_session': {
                                  'name': kyThi!['tenKyThi'],
                                },
                              },
                            ),
                          ),
                        );
                      },
                      child: Text('Bắt đầu làm bài',
                          style: TextStyle(fontSize: 18)),
                    ),
                  ),
                  const SizedBox(width: 24),
                  Expanded(
                    child: Button(
                      style: ButtonStyle(
                        padding: ButtonState.all(
                            const EdgeInsets.symmetric(vertical: 18)),
                        backgroundColor: ButtonState.all(Colors.grey[40]),
                      ),
                      onPressed: _logout,
                      child: const Text('Đăng xuất',
                          style: TextStyle(fontSize: 16)),
                    ),
                  ),
                ],
              ),
            ],
          ),
        ),
      ),
    );
  }
}
