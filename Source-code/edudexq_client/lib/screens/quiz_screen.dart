import 'package:fluent_ui/fluent_ui.dart';
import 'package:flutter/services.dart';
import 'dart:async';
import 'result_screen.dart';
import 'package:window_manager/window_manager.dart';
import 'dart:io' show Platform, File;
import 'package:provider/provider.dart';
import '../theme.dart';
import 'dart:convert';
import 'package:flutter/foundation.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:cached_network_image/cached_network_image.dart';
import 'package:http/http.dart' as http;
import 'package:intl/intl.dart';
import 'package:file_picker/file_picker.dart';
import 'package:edudex_quiz_client/screens/dashboard/dashboard_screen.dart';

class QuizScreen extends StatefulWidget {
  final Map<String, dynamic> examInfo;

  const QuizScreen({
    super.key,
    required this.examInfo,
  });

  @override
  State<QuizScreen> createState() => _QuizScreenState();
}

class _QuizScreenState extends State<QuizScreen> with WindowListener {
  // biến lưu câu hỏi hiện tại
  int _currentQuestionIndex = 0;

  // đáp án được chọn cho câu hỏi hiện tại
  int? _selectedAnswerIndex;

  // biến đếm thời gian làm bài
  Timer? _timer;

  int _remainingSeconds = 1 * 60; // 1 phút

  // biến trạng thái đang nộp bài
  bool _isSubmitting = false;

  // cỡ chữ
  double _fontSize = 16.0;

  // biến lưu danh sách câu hỏi từ file json
  List<Map<String, dynamic>> _questions = [];

  // trạng thái đang tải câu hỏi
  bool _isLoading = true;

  // biến lưu đáp án của người dùng (số thứ tự câu hỏi -> đáp án đã chọn)
  Map<int, int?> _userAnswers = {};

  // biến controller cuộn màn hình
  final ScrollController _scrollController = ScrollController();

  // biến chiều cao ước tính cho mỗi câu hỏi
  final double _questionHeight = 300;

  Map<String, dynamic>? _studentData;
  List<dynamic>? _examsData;

  // Thêm biến lưu thông tin đề thi
  Map<String, dynamic>? _testPaperDetails;

  // Thêm biến lưu tổng thời gian
  int _totalSeconds = 0;

  final DateTime _startedAt = DateTime.now();

  // Thêm biến lưu log
  final List<String> _actionLogs = [];

  // Biến để lưu nội dung
  String submissionContent = '';

  // Thêm map để lưu key cho từng câu hỏi
  final Map<int, GlobalKey> _questionKeys = {};

  // Hàm thêm log
  void _addLog(String action) {
    final now = DateTime.now();
    final timestamp = DateFormat('dd-MM-yyyy-HH-mm-ss').format(now);
    _actionLogs.add('[$timestamp] $action');
  }

  // hàm cuộn đến câu hỏi được chọn
  void _scrollToQuestion(int index) {
    if (_scrollController.hasClients && _questionKeys.containsKey(index)) {
      // Lấy vị trí hiện tại của câu hỏi được chọn
      final RenderBox renderBox =
          _questionKeys[index]!.currentContext!.findRenderObject() as RenderBox;
      final position = renderBox.localToGlobal(Offset.zero);

      // Scroll đến vị trí của câu hỏi
      _scrollController.animateTo(
        _scrollController.offset +
            position.dy -
            250, // trừ 250 kích thước của header
        duration: const Duration(milliseconds: 500),
        curve: Curves.easeInOut,
      );
    }
  }

  @override
  void initState() {
    super.initState();
    _loadData();
    _setupFullScreen();

    // Lấy thời gian từ examInfo
    _totalSeconds = widget.examInfo['exam']['duration'] * 60;
    _remainingSeconds = _totalSeconds;

    _startTimer();
    windowManager.addListener(this);
    _initializeWindow();
    RawKeyboard.instance.addListener(_handleKeyEvent);

    // Log kích thước màn hình khi khởi tạo
    WidgetsBinding.instance.addPostFrameCallback((_) {
      final size = MediaQuery.of(context).size;
      _addLog(
          'kích thước màn hình: ${size.width.round()}x${size.height.round()}');
      _addLog('bắt đầu làm bài');
    });
  }

  Future<void> _loadData() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final serverUrl = prefs.getString('server_url');
      final thiSinhStr = prefs.getString('thiSinh');
      final deThiStr = prefs.getString('deThi');
      if (serverUrl == null || thiSinhStr == null || deThiStr == null) {
        if (mounted) Navigator.pushReplacementNamed(context, '/');
        return;
      }
      final thiSinh = json.decode(thiSinhStr);
      final deThi = json.decode(deThiStr);

      final response = await http.get(
        Uri.parse(
            '$serverUrl/lam-bai/?soBaoDanhId=${thiSinh['soBaoDanhId']}&deThiId=${deThi['id']}'),
      );
      final data = json.decode(response.body);
      if (response.statusCode == 200 && data['success'] == true) {
        final quizData = data['data'];
        setState(() {
          _studentData = quizData['thiSinh'];
          _testPaperDetails = quizData['deThi'];
          _questions = List<Map<String, dynamic>>.from(quizData['cauHoi']);
          _isLoading = false;
        });
      } else {
        throw Exception(data['message'] ?? 'Không thể tải đề thi');
      }
    } catch (e) {
      print('❌ Error loading data: $e');
      setState(() {
        _isLoading = false;
      });
    }
  }

  void _setupFullScreen() {
    SystemChrome.setEnabledSystemUIMode(SystemUiMode.manual, overlays: []);
    // Ngăn chặn Alt+Tab
    RawKeyboard.instance.addListener(_handleKeyEvent);
  }

  void _handleKeyEvent(RawKeyEvent event) {
    if (event is RawKeyDownEvent) {
      _addLog('phím: ${event.logicalKey.keyLabel}');
    }
    // Chặn các phím Windows/Super
    if (event.isMetaPressed) {
      return;
    }

    // Chặn Alt+Tab, Windows+Tab
    if (event.isAltPressed || event.isMetaPressed) {
      if (event.logicalKey == LogicalKeyboardKey.tab) {
        return;
      }
    }

    // Chặn Windows+D (Show desktop)
    if (event.isMetaPressed && event.logicalKey == LogicalKeyboardKey.keyD) {
      return;
    }

    // Chặn Ctrl+Alt+Delete
    if (event.isControlPressed && event.isAltPressed) {
      if (event.logicalKey == LogicalKeyboardKey.delete) {
        return;
      }
    }

    // Chặn Alt+F4
    if (event.isAltPressed && event.logicalKey == LogicalKeyboardKey.f4) {
      return;
    }

    // Chặn Ctrl+W
    if (event.isControlPressed && event.logicalKey == LogicalKeyboardKey.keyW) {
      return;
    }

    // Chặn các phím chức năng F1-F12
    if (event.logicalKey.keyLabel.startsWith('F') &&
        event.logicalKey.keyLabel.length <= 3) {
      return;
    }

    // Chặn Ctrl+Shift+Esc (Task Manager)
    if (event.isControlPressed &&
        event.isShiftPressed &&
        event.logicalKey == LogicalKeyboardKey.escape) {
      return;
    }

    // Chặn Alt+Esc
    if (event.isAltPressed && event.logicalKey == LogicalKeyboardKey.escape) {
      return;
    }
  }

  void _startTimer() {
    _timer = Timer.periodic(const Duration(seconds: 1), (timer) {
      setState(() {
        if (_remainingSeconds > 0) {
          _remainingSeconds--;
        } else {
          _timer?.cancel();
          // hiện modal hết giờ
          showDialog(
            context: context,
            barrierDismissible: false,
            builder: (context) => ContentDialog(
              title: const Text('Hết giờ làm bài'),
              content: const Text(
                  'Đã hết thời gian làm bài, hệ thống sẽ tự động nộp bài.'),
              actions: [
                FilledButton(
                  child: const Text('OK'),
                  onPressed: () {
                    Navigator.pop(context);
                    _submitTest();
                  },
                ),
              ],
            ),
          );
        }
      });
    });
  }

  String get _formattedTime {
    int minutes = _remainingSeconds ~/ 60;
    int seconds = _remainingSeconds % 60;
    return '${minutes.toString().padLeft(2, '0')}:${seconds.toString().padLeft(2, '0')}';
  }

  Future<void> _submitTest() async {
    setState(() {
      _isSubmitting = true;
    });
    try {
      final prefs = await SharedPreferences.getInstance();
      final thiSinhStr = prefs.getString('thiSinh');
      final deThiStr = prefs.getString('deThi');
      final serverUrl = prefs.getString('server_url');
      if (thiSinhStr == null || deThiStr == null || serverUrl == null) {
        throw Exception('Không tìm thấy thông tin thí sinh hoặc đề thi');
      }
      final thiSinh = json.decode(thiSinhStr);
      final deThi = json.decode(deThiStr);

      // Map<String, dynamic> dapAn = {};
      final Map<String, dynamic> dapAn = {};
      for (int i = 0; i < _questions.length; i++) {
        final q = _questions[i];
        final cauHoiId = q['cauHoi']['id'];
        final dapAnList = q['dapAn'] as List;
        final selectedIdx = _userAnswers[i];
        if (selectedIdx != null) {
          final dapAnId = dapAnList[selectedIdx]['id'];
          dapAn[cauHoiId.toString()] = dapAnId;
        }
      }

      final response = await http.post(
        Uri.parse('$serverUrl/nop-bai/index.php'),
        headers: {'Content-Type': 'application/json'},
        body: json.encode({
          'soBaoDanhId': thiSinh['soBaoDanhId'],
          'deThiId': deThi['id'],
          'dapAn': dapAn,
        }),
      );
      final data = json.decode(response.body);
      if (response.statusCode == 200 && data['success'] == true) {
        await prefs.setString('ketQua', json.encode(data['data']['ketQua']));
        if (mounted) Navigator.pushReplacementNamed(context, '/ket-qua');
      } else {
        throw Exception(data['message'] ?? 'Có lỗi xảy ra khi nộp bài');
      }
    } catch (e) {
      print('❌ Submit error: $e');
      if (mounted) {
        showDialog(
          context: context,
          builder: (context) => ContentDialog(
            title: const Text('Lỗi'),
            content: Text('Lỗi khi nộp bài: ${e.toString()}'),
            actions: [
              Button(
                  child: const Text('Đóng'),
                  onPressed: () => Navigator.pop(context))
            ],
          ),
        );
      }
    } finally {
      setState(() {
        _isSubmitting = false;
      });
    }
  }

  Future<void> _initializeWindow() async {
    try {
      if (const bool.fromEnvironment('dart.library.io')) {
        await windowManager.ensureInitialized();
        await windowManager.setFullScreen(true);
        await windowManager.setAlwaysOnTop(true);
        await windowManager.focus();
      }
    } catch (e) {
      // Bỏ qua lỗi khi debug web
    }
  }

  @override
  void dispose() {
    try {
      if (const bool.fromEnvironment('dart.library.io')) {
        windowManager.removeListener(this);
        windowManager.setFullScreen(false);
        windowManager.setAlwaysOnTop(false);
      }
    } catch (e) {
      // Bỏ qua lỗi khi chạy trên web
    }
    _scrollController.dispose();
    _timer?.cancel();
    RawKeyboard.instance.removeListener(_handleKeyEvent);
    SystemChrome.setEnabledSystemUIMode(SystemUiMode.manual,
        overlays: SystemUiOverlay.values);
    super.dispose();
  }

  @override
  void onWindowClose() async {
    bool isPreventClose = await windowManager.isPreventClose();
    if (isPreventClose) {
      showDialog(
        context: context,
        builder: (_) {
          return ContentDialog(
            title: const Text('Xác nhận'),
            content: const Text('Bạn có chắc chắn muốn thoát khỏi bài thi?'),
            actions: [
              Button(
                child: const Text('Không'),
                onPressed: () {
                  Navigator.of(context).pop();
                },
              ),
              FilledButton(
                child: const Text('Có'),
                onPressed: () {
                  Navigator.of(context).pop();
                  windowManager.destroy();
                },
              ),
            ],
          );
        },
      );
    }
  }

  // Thêm hàm xử lý click chuột
  void _handleMouseClick(TapDownDetails details) {
    _addLog(
        'click chuột tại vị trí: (${details.globalPosition.dx.round()}, ${details.globalPosition.dy.round()})');
  }

  // Cập nhật hàm chọn đáp án
  void _selectAnswer(int questionIndex, int answerIndex) {
    setState(() {
      _userAnswers[questionIndex] = answerIndex;
    });
    _addLog('chọn đáp án ${answerIndex + 1} cho câu ${questionIndex + 1}');
  }

  // Cập nhật hàm thay đổi font size
  void _changeFontSize(double newSize) {
    setState(() {
      _fontSize = newSize;
    });
    _addLog('thay đổi cỡ chữ: $_fontSize');
  }

  // Cập nhật hàm chuyển đổi theme
  void _toggleTheme(bool isDark) {
    final appTheme = context.read<AppTheme>();
    appTheme.mode = isDark ? ThemeMode.dark : ThemeMode.light;
    _addLog('chuyển sang ${isDark ? "chế độ tối" : "chế độ sáng"}');
  }

  @override
  Widget build(BuildContext context) {
    final appTheme = context.watch<AppTheme>();

    if (_isLoading) {
      return const Center(child: ProgressRing());
    }

    return GestureDetector(
      onTapDown: _handleMouseClick,
      child: NavigationView(
        appBar: NavigationAppBar(
          automaticallyImplyLeading: false,
          leading: Row(
            children: const [
              SizedBox(width: 8),
              Icon(FluentIcons.defender_app),
              SizedBox(width: 4),
              Text('Được áp dụng công nghệ chống gian lận'),
            ],
          ),
          actions: Row(
            mainAxisAlignment: MainAxisAlignment.end,
            children: [
              // Nút điều chỉnh cỡ chữ
              Row(
                children: [
                  IconButton(
                    icon: const Icon(FluentIcons.font_decrease),
                    onPressed: () {
                      if (_fontSize > 12) {
                        _changeFontSize(_fontSize - 2);
                      }
                    },
                  ),
                  IconButton(
                    icon: const Icon(FluentIcons.font_size),
                    onPressed: () {
                      _changeFontSize(16.0);
                    },
                  ),
                  IconButton(
                    icon: const Icon(FluentIcons.font_increase),
                    onPressed: () {
                      if (_fontSize < 24) {
                        _changeFontSize(_fontSize + 2);
                      }
                    },
                  ),
                  const SizedBox(width: 16),
                ],
              ),
              // Nút chuyển đổi theme
              Padding(
                padding: const EdgeInsets.only(right: 8.0),
                child: ToggleSwitch(
                  content: const Text('Chế độ tối'),
                  checked: FluentTheme.of(context).brightness.isDark,
                  onChanged: _toggleTheme,
                ),
              ),
            ],
          ),
        ),
        content: ScaffoldPage(
          padding: EdgeInsets.zero,
          content: Column(
            children: [
              // Header với thông tin thí sinh và bài thi
              Container(
                padding: const EdgeInsets.all(16),
                decoration: BoxDecoration(
                  border: Border(bottom: BorderSide(color: Colors.grey[30]!)),
                ),
                child: Row(
                  children: [
                    // Ảnh và thông tin thí sinh
                    Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        const Text('Thông tin thí sinh',
                            style: TextStyle(fontWeight: FontWeight.bold)),
                        const SizedBox(height: 8),
                        Text(
                            'Mã sinh viên: ${_studentData?['maSinhVien'] ?? ''}'),
                        Text('Họ và tên: ${_studentData?['hoTen'] ?? ''}'),
                        Text(
                            'Số báo danh: ${_studentData?['soBaoDanh'] ?? ''}'),
                      ],
                    ),
                    const Spacer(),
                    // Thông tin bài thi
                    Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        const Text('Thông tin bài thi',
                            style: TextStyle(fontWeight: FontWeight.bold)),
                        const SizedBox(height: 8),
                        Text('Môn thi: ${widget.examInfo['subject']['name']}'),
                        Text(
                            'Tên đề thi: ${_testPaperDetails?['tenDeThi'] ?? ''}'),
                        Text(
                            'Số câu hỏi: ${_testPaperDetails?['soCau'] ?? ''}'),
                        Text(
                            'Thời gian: ${_testPaperDetails?['thoiGianLamBai'] ?? ''} phút'),
                      ],
                    ),
                  ],
                ),
              ),
              // Nội dung bài thi
              Expanded(
                child: Row(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    // Phần câu hỏi
                    Expanded(
                      child: Container(
                        padding: const EdgeInsets.all(16),
                        decoration: BoxDecoration(
                          border: Border(
                              right: BorderSide(color: Colors.grey[30]!)),
                        ),
                        child: SingleChildScrollView(
                          controller: _scrollController,
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: List.generate(
                              _questions.length,
                              (index) {
                                final q = _questions[index];
                                final cauHoi = q['cauHoi'];
                                final dapAnList = q['dapAn'] as List;
                                return Column(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  children: [
                                    Text(
                                      'Câu ${index + 1}: ${cauHoi['noiDung']}',
                                      style: TextStyle(
                                        color: const Color(0xFFD83B01),
                                        fontWeight: FontWeight.bold,
                                        fontSize: _fontSize,
                                      ),
                                    ),
                                    const SizedBox(height: 16),
                                    ...List.generate(dapAnList.length, (i) {
                                      final da = dapAnList[i];
                                      return RadioButton(
                                        checked: _userAnswers[index] == i,
                                        onChanged: (v) {
                                          setState(() {
                                            _userAnswers[index] = i;
                                          });
                                        },
                                        content: Text(
                                          '${String.fromCharCode(65 + i)}. ${da['noiDung']}',
                                          style: TextStyle(
                                              fontSize: _fontSize - 2),
                                        ),
                                      );
                                    }),
                                  ],
                                );
                              },
                            ),
                          ),
                        ),
                      ),
                    ),

                    // Phần bên phải
                    Container(
                      width: 300,
                      padding: const EdgeInsets.all(16),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.stretch,
                        children: [
                          Text(
                            'Thời gian còn lại: $_formattedTime',
                            style: const TextStyle(
                              fontSize: 16,
                              fontWeight: FontWeight.bold,
                            ),
                          ),
                          const SizedBox(height: 8),
                          // Thêm progress bar thời gian
                          ProgressBar(
                            value: _totalSeconds > 0
                                ? (_remainingSeconds / _totalSeconds * 100)
                                    .clamp(0, 100)
                                : 0,
                            backgroundColor: Colors.grey[30],
                            activeColor:
                                _remainingSeconds < (_totalSeconds * 0.25)
                                    ? Colors.red
                                    : (_remainingSeconds < (_totalSeconds * 0.5)
                                        ? Colors.orange
                                        : Colors.blue),
                          ),
                          const SizedBox(height: 16),
                          const Text(
                            'Bảng đáp án',
                            style: TextStyle(
                              fontSize: 16,
                              fontWeight: FontWeight.bold,
                            ),
                          ),
                          const SizedBox(height: 16),
                          Expanded(
                            child: GridView.builder(
                              gridDelegate:
                                  const SliverGridDelegateWithFixedCrossAxisCount(
                                crossAxisCount: 4,
                                childAspectRatio: 1,
                                crossAxisSpacing: 8,
                                mainAxisSpacing: 8,
                              ),
                              itemCount: _questions.length,
                              itemBuilder: (context, index) {
                                final isAnswered =
                                    _userAnswers.containsKey(index);
                                final isSelected =
                                    _currentQuestionIndex == index;

                                return Button(
                                  onPressed: () {
                                    setState(() {
                                      _currentQuestionIndex = index;
                                      _selectedAnswerIndex =
                                          _userAnswers[index];
                                    });
                                    _scrollToQuestion(index);
                                  },
                                  style: ButtonStyle(
                                    padding: ButtonState.all(EdgeInsets.zero),
                                    backgroundColor: ButtonState.all(
                                      isAnswered
                                          ? Colors.green.lightest
                                          : (isSelected
                                              ? Colors.blue.lightest
                                              : null),
                                    ),
                                  ),
                                  child: Stack(
                                    children: [
                                      Center(
                                        child: Text('${index + 1}'),
                                      ),
                                      if (isAnswered)
                                        Positioned(
                                          right: 4,
                                          bottom: 4,
                                          child: Text(
                                            String.fromCharCode(
                                                65 + _userAnswers[index]!),
                                            style:
                                                const TextStyle(fontSize: 10),
                                          ),
                                        ),
                                    ],
                                  ),
                                );
                              },
                            ),
                          ),
                          const SizedBox(height: 16),
                          FilledButton(
                            onPressed: _isSubmitting ? null : _submitTest,
                            child: _isSubmitting
                                ? const Row(
                                    mainAxisSize: MainAxisSize.min,
                                    children: [
                                      SizedBox(
                                        width: 16,
                                        height: 16,
                                        child: ProgressRing(),
                                      ),
                                      SizedBox(width: 8),
                                      Text('Đang nộp bài...'),
                                    ],
                                  )
                                : const Text('Nộp bài'),
                          ),
                          const SizedBox(height: 8),
                          const Text(
                            'Bài thi kết thúc khi hết thời gian hoặc khi thí sinh nhấn vào nút "Nộp bài"',
                            style: TextStyle(
                                color: Color(0xFFD83B01), fontSize: 12),
                            textAlign: TextAlign.center,
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
