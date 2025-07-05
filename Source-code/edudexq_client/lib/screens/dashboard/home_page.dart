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
    final theme = FluentTheme.of(context);
    final isDark = theme.brightness == Brightness.dark;
    final cardColor = isDark ? Colors.grey[180] : Colors.white;
    final borderRadius = BorderRadius.circular(20);
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

    if (thiSinh == null || kyThi == null || deThi == null) {
      return const Center(child: ProgressRing());
    }

    return Center(
      child: Container(
        constraints: const BoxConstraints(maxWidth: 700),
        padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 32),
        child: SingleChildScrollView(
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              // Tiêu đề
              Row(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  Icon(FluentIcons.education, size: 32, color: Colors.blue),
                  const SizedBox(width: 10),
                  Text('Hệ thống thi trắc nghiệm',
                      style: theme.typography.titleLarge?.copyWith(
                        fontSize: 28,
                        color: textColor,
                        fontWeight: FontWeight.w600,
                      )),
                ],
              ),
              const SizedBox(height: 6),
              Text('Chào mừng bạn đến với hệ thống thi cử EduDexQ!',
                  style: TextStyle(color: subTextColor, fontSize: 15)),
              const SizedBox(height: 18),
              InfoBar(
                title: const Text('Lưu ý'),
                content: const Text(
                    'Vui lòng kiểm tra thông tin cá nhân và đề thi trước khi bắt đầu.'),
                severity: InfoBarSeverity.info,
                isLong: true,
              ),
              const SizedBox(height: 28),

              // Thông tin thí sinh & đề thi
              Row(
                children: [
                  Expanded(
                    child: Container(
                      decoration: BoxDecoration(
                        color: cardColor,
                        borderRadius: borderRadius,
                        boxShadow: cardShadow,
                      ),
                      padding: const EdgeInsets.all(20),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Row(
                            children: [
                              Icon(FluentIcons.contact,
                                  color: Colors.blue, size: 26),
                              const SizedBox(width: 8),
                              Text('Thông tin thí sinh',
                                  style: TextStyle(
                                    fontWeight: FontWeight.w600,
                                    fontSize: 16,
                                    color: Colors.blue,
                                  )),
                            ],
                          ),
                          const SizedBox(height: 10),
                          Text('Họ tên: ${thiSinh!['hoTen']}',
                              style: TextStyle(fontSize: 15, color: textColor)),
                          Text('Mã sinh viên: ${thiSinh!['maSinhVien']}',
                              style: TextStyle(fontSize: 15, color: textColor)),
                          Text('Số báo danh: ${thiSinh!['soBaoDanh']}',
                              style: TextStyle(fontSize: 15, color: textColor)),
                        ],
                      ),
                    ),
                  ),
                  const SizedBox(width: 20),
                  Expanded(
                    child: Container(
                      decoration: BoxDecoration(
                        color: cardColor,
                        borderRadius: borderRadius,
                        boxShadow: cardShadow,
                      ),
                      padding: const EdgeInsets.all(20),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Row(
                            children: [
                              Icon(FluentIcons.task_logo,
                                  color: Colors.orange, size: 26),
                              const SizedBox(width: 8),
                              Text('Thông tin đề thi',
                                  style: TextStyle(
                                    fontWeight: FontWeight.w600,
                                    fontSize: 16,
                                    color: Colors.orange,
                                  )),
                            ],
                          ),
                          const SizedBox(height: 10),
                          Text('Tên đề thi: ${deThi!['tenDeThi']}',
                              style: TextStyle(fontSize: 15, color: textColor)),
                          Text('Số câu hỏi: ${deThi!['soCau']}',
                              style: TextStyle(fontSize: 15, color: textColor)),
                          Text(
                              'Thời gian làm bài: ${deThi!['thoiGianLamBai']} phút',
                              style: TextStyle(fontSize: 15, color: textColor)),
                        ],
                      ),
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 22),

              // Thời gian thi
              Container(
                width: double.infinity,
                decoration: BoxDecoration(
                  color: cardColor,
                  borderRadius: borderRadius,
                  boxShadow: cardShadow,
                ),
                padding: const EdgeInsets.all(20),
                child: Row(
                  children: [
                    Icon(FluentIcons.clock, color: Colors.green, size: 26),
                    const SizedBox(width: 8),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text('Thời gian thi',
                              style: TextStyle(
                                  fontWeight: FontWeight.w600,
                                  fontSize: 16,
                                  color: Colors.green)),
                          const SizedBox(height: 8),
                          Text(
                              'Thời gian bắt đầu: ${_formatDateTime(kyThi!['thoiGianBatDau'])}',
                              style: TextStyle(fontSize: 15, color: textColor)),
                          Text(
                              'Thời gian kết thúc: ${_formatDateTime(kyThi!['thoiGianKetThuc'])}',
                              style: TextStyle(fontSize: 15, color: textColor)),
                          const SizedBox(height: 6),
                          ValueListenableBuilder<DateTime>(
                            valueListenable: _now,
                            builder: (context, value, _) {
                              return Text(_countdownText(),
                                  style: TextStyle(
                                      fontSize: 18,
                                      fontWeight: FontWeight.bold,
                                      color: Colors.green));
                            },
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
              ),
              const SizedBox(height: 32),

              // Nút
              Row(
                children: [
                  Expanded(
                    child: FilledButton(
                      style: ButtonStyle(
                        padding: ButtonState.all(
                            const EdgeInsets.symmetric(vertical: 18)),
                        backgroundColor: ButtonState.all(Colors.blue),
                        shape: ButtonState.all(
                            RoundedRectangleBorder(borderRadius: borderRadius)),
                      ),
                      onPressed: () {
                        Navigator.pushReplacement(
                          context,
                          FluentPageRoute(
                            builder: (context) => QuizScreen(
                              examInfo: {
                                'subject': {'name': kyThi!['monHoc']},
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
                                'room': {'name': '', 'facility': ''},
                                'test_session': {'name': kyThi!['tenKyThi']},
                              },
                            ),
                          ),
                        );
                      },
                      child: const Text('Bắt đầu làm bài',
                          style: TextStyle(fontSize: 18, color: Colors.white)),
                    ),
                  ),
                  const SizedBox(width: 20),
                  Expanded(
                    child: Button(
                      style: ButtonStyle(
                        padding: ButtonState.all(
                            const EdgeInsets.symmetric(vertical: 18)),
                        backgroundColor: ButtonState.all(Colors.transparent),
                        shape: ButtonState.all(RoundedRectangleBorder(
                          borderRadius: borderRadius,
                          side: BorderSide(color: Colors.grey[120]!),
                        )),
                      ),
                      onPressed: _logout,
                      child: Text('Đăng xuất',
                          style: TextStyle(fontSize: 16, color: textColor)),
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
