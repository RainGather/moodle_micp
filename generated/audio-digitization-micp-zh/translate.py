#!/usr/bin/env python3
"""Translate the English MICP lesson HTML to Chinese."""

import re

ENGLISH_FILE = '/home/q/sync/projects/202603_moodle_micp/generated/audio-digitization-micp/index.html'
CHINESE_FILE = '/home/q/sync/projects/202603_moodle_micp/generated/audio-digitization-micp-zh/index.html'

# Translation dictionary: English -> Chinese
TRANSLATIONS = {
    # Page title
    'How sound becomes data you can store and edit': '声音如何变成可以存储和编辑的数据',

    # Hero section
    'MICP interactive lesson package': 'MICP 互动课程资源包',
    'Sound starts as smooth pressure changes in air. To make it editable by a computer, we sample the wave,\n          round each measurement into a level, encode those levels in binary, and then process the stored numbers.': '声音始于空气中压力的连续变化。要让计算机能够编辑声音，我们需要对波形进行采样，将每次测量值四舍五入到某一等级，用二进制编码这些等级，然后对存储的数字进行处理。',
    '6 stages': '6 个阶段',
    'Progressive reveal from analog motion to digital editing.': '从模拟运动到数字编辑的渐进式揭示。',
    '5 visual labs': '5 个可视化实验',
    'Waveform, sampling, quantization, binary encoding, and filtering.': '波形、采样、量化、二进制编码和滤波。',
    '1 synthesis': '1 次综合练习',
    'Explain the whole chain in your own words before submitting.': '在提交前用自己的话解释整个链条。',
    'Signal journey': '信号之旅',
    'Analog': '模拟信号',
    'Sampled': '采样信号',
    'Binary': '二进制',
    'Every activity below reveals one more transformation step. Explore each visualization, answer the checkpoint,\n          and the next concept unlocks.': '下面的每个活动都会揭示下一个转换步骤。探索每个可视化内容，回答检查点问题，下一个概念就会解锁。',

    # Step 1
    'Analog sound is continuous': '模拟声音是连续的',
    'Ready': '就绪',
    'Continuous waveform': '连续波形',
    'Sample markers appear later': '采样标记稍后出现',
    'Processed wave appears near the end': '处理后的波形在最后出现',
    'What you are seeing': '你所看到的',
    'A microphone tracks air pressure over time. The wave does not jump from box to box. It bends smoothly,\n                which is why the computer needs a strategy to capture snapshots of it.': '麦克风随时间追踪气压变化。波形不是在方框之间跳跃，而是平滑弯曲，这就是为什么计算机需要一个策略来捕捉它的快照。',
    'Reveal the explanation to confirm that analog sound is a continuously varying signal.': '揭示解释以确认模拟声音是连续变化的信号。',
    'Reveal analog story': '揭示模拟信号的故事',

    # Step 2
    'Sampling turns a smooth wave into snapshots': '采样将平滑的波形转换为快照',
    'Locked': '已锁定',
    'Sampling density:': '采样密度：',
    'snapshots across the same wave': '个快照横跨同一波形',
    'Freeze these sampling points': '冻结这些采样点',
    'Checkpoint': '检查点',
    'Try a low and a high sample count. Then choose which option best preserves the shape of the original wave.': '尝试低采样数和高采样数。然后选择哪个选项最能保留原始波形的形状。',
    'Low density': '低密度',
    'Too few samples capture only a rough outline.': '采样太少只能捕捉到粗糙的轮廓。',
    'Medium density': '中密度',
    'Better, but still misses some fast changes.': '更好，但仍然错过一些快速变化。',
    'High density': '高密度',
    'More snapshots preserve the wave much more faithfully.': '更多的快照能更忠实地保留波形。',

    # Step 3
    'Quantization rounds each sample into levels': '量化将每个采样四舍五入到等级',
    'Bit depth:': '比特深度：',
    'bits': '比特',
    'More bits create more available vertical steps.': '更多比特创造更多可用的垂直步数。',
    'Smaller steps reduce quantization error.': '步长越小，量化误差越小。',
    'Large rounding step': '大量化步长',
    'Each added bit doubles the number of possible levels. Which bit depth gives': '每增加一个比特，可能性等级的数量就会翻倍。哪个比特深度给出',
    'quantization levels?': '个量化等级？',
    'That gives': '那给出',
    'levels.': '个等级。',

    # Step 4
    'Those quantized levels are stored in binary': '这些量化等级以二进制形式存储',
    'Sample-to-code explorer': '采样编码浏览器',
    'Select a stored sample to inspect its decimal level and binary representation.': '选择一个存储的采样来检查其十进制等级和二进制表示。',
    'Sample 1': '采样 1',
    'Quantized level': '量化等级',
    'out of': '共',
    'In a 3-bit system, which code stores decimal level': '在3比特系统中，哪个编码存储十进制等级',
    'means': '表示',
    'This is decimal': '这是十进制',

    # Step 5
    'Digital processing manipulates the stored numbers': '数字处理操作存储的数字',
    'Gain multiplier:': '增益倍数：',
    'Smoothing window:': '平滑窗口：',
    'samples': '个采样',
    'Gain makes the wave taller. Smoothing averages neighboring samples. Which operation mainly removes rapid zig-zag variation?': '增益使波形更高。平滑对相邻采样取平均。哪种操作主要消除快速的锯齿状变化？',
    'Increase gain': '增加增益',
    'This scales the values up and down.': '这会上下缩放数值。',
    'Apply smoothing': '应用平滑',
    'This averages neighboring samples to tame fast variations.': '这会对相邻采样取平均以抑制快速变化。',
    'Change bit depth': '改变比特深度',
    'This affects storage precision, not post-recording filtering alone.': '这影响存储精度，而非仅影响录制后滤波。',

    # Step 6
    'Synthesize the full story': '综合完整的故事',
    'Your explanation': '你的解释',
    'In 2–4 sentences, explain how sound moves from an analog waveform to digital storage and how a computer can then process it.': '用2-4句话解释声音如何从模拟波形移动到数字存储，以及计算机如何处理它。',
    'Final synthesis reflection': '最终综合反思',
    'Example: A microphone turns air-pressure changes into a waveform. The computer samples the wave at many moments, rounds each sample into one of a limited number of levels, stores those levels as binary, and then can change the numbers to amplify or smooth the sound.': '示例：麦克风将气压变化转换为波形。计算机在多个时刻对波形进行采样，将每个采样四舍五入到有限等级之一，将这些等级存储为二进制，然后可以改变数值来放大或平滑声音。',
    'Save synthesis': '保存综合练习',
    'Write a brief explanation before saving.': '保存前请简要解释。',
    'Saved. Your explanation is ready to submit.': '已保存。你的解释已准备好提交。',
    'Concept chain': '概念链条',
    'Continuous pressure changes.': '连续的压力变化。',
    'Take snapshots over time.': '随时间获取快照。',
    'Round each snapshot into levels.': '将每个快照四舍五入到等级。',
    'Encode levels as 0s and 1s.': '将等级编码为0和1。',
    'Edit the stored numbers.': '编辑存储的数字。',

    # Progress panel
    'Progress map': '进度地图',
    'Analog waveform': '模拟波形',
    'Current': '当前',
    'Sampling': '采样',
    'Quantization': '量化',
    'Binary storage': '二进制存储',
    'Processing': '处理',
    'Final synthesis': '最终综合',
    'Locked': '已锁定',
    'Complete': '已完成',
    'Current / available': '当前/可用',
    'Submission status': '提交状态',
    'Interact with each checkpoint, then submit your action log to Moodle.': '与每个检查点互动，然后将你的操作日志提交到 Moodle。',
    'Submit attempt': '提交尝试',
    'Action evidence log': '操作证据日志',

    # Feedback strings (JS)
    'Correct: more sample points preserve the waveform shape more accurately.': '正确：更多的采样点能更准确地保留波形形状。',
    'Try again: lower sample density skips over more of the changing waveform.': '再试一次：较低的采样密度会错过更多变化的波形。',
    'Correct: 5 bits = 2^5 = 32 quantization levels.': '正确：5比特 = 2^5 = 32 个量化等级。',
    'Not yet: each extra bit doubles the levels, so count powers of two.': '还不对：每增加一个比特，等级数就翻倍，所以要数2的幂。',
    'Correct: 101 means 4 + 1 = 5.': '正确：101 表示 4 + 1 = 5。',
    'Try converting each bit position into its decimal value again.': '再试着把每个比特位转换成对应的十进制值。',
    'Correct: smoothing averages neighbors, so sharp zig-zags shrink.': '正确：平滑对相邻点取平均，所以尖锐的锯齿变小。',
    'Look at the processed waveform again: scaling is not the same as smoothing.': '再看看处理后的波形：缩放和平滑不一样。',
    'Analog sound is continuous: the microphone signal can take any value along the curve before the computer samples it.': '模拟声音是连续的：在计算机采样之前，麦克风信号可以沿曲线取任何值。',
    'More sample points capture more of the original shape.': '更多的采样点能捕捉更多原始形状。',
    'Very fine rounding step': '非常精细的量化步长',
    'Smaller rounding step': '较小的量化步长',
    'Large rounding step': '大量化步长',
    'Original stored samples': '原始存储的采样',
    'Processed sample values': '处理后的采样值',

    # Submission messages
    'Your raw action log was submitted successfully.': '你的原始操作日志已成功提交。',
    'Submitting your raw action log...': '正在提交你的原始操作日志...',
    'Submission failed. Please try again.': '提交失败。请重试。',
}

def translate_file():
    with open(ENGLISH_FILE, 'r', encoding='utf-8') as f:
        content = f.read()

    # Sort by length descending to avoid partial replacements
    sorted_translations = sorted(TRANSLATIONS.items(), key=lambda x: len(x[0]), reverse=True)

    for english, chinese in sorted_translations:
        content = content.replace(english, chinese)

    with open(CHINESE_FILE, 'w', encoding='utf-8') as f:
        f.write(content)

    print(f"Translated {len(TRANSLATIONS)} strings")
    print(f"Output: {CHINESE_FILE}")

if __name__ == '__main__':
    translate_file()
