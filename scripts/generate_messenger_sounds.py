#!/usr/bin/env python3
"""
Generate professional, royalty-free, public-domain 16-bit 44.1kHz PCM WAV audio assets
for Bondhoo Messenger's Three Independent Notification Sounds & Ringtone System.
"""

import os
import math
import struct
import wave

SAMPLE_RATE = 44100

def create_wav(filename, samples):
    os.makedirs(os.path.dirname(filename), exist_ok=True)
    with wave.open(filename, 'w') as wav_file:
        wav_file.setnchannels(1)      # Mono
        wav_file.setsampwidth(2)     # 16-bit
        wav_file.setframerate(SAMPLE_RATE)
        
        raw_data = bytearray()
        for s in samples:
            # Clamp to 16-bit signed range [-32767, 32767]
            clamped = max(-32767, min(32767, int(s * 32767)))
            raw_data.extend(struct.pack('<h', clamped))
        wav_file.writeframes(raw_data)
    print(f"Generated {filename}: {len(samples)/SAMPLE_RATE:.2f}s ({len(raw_data)} bytes)")

def envelope(t, attack, decay, total_len):
    if t < attack:
        return t / attack
    if t > total_len:
        return 0.0
    progress = (t - attack) / max(0.001, total_len - attack)
    return math.exp(-decay * progress)

# -------------------------------------------------------------
# 1. INCOMING CALL RINGTONES (~2.8s - 3.2s)
# -------------------------------------------------------------

def gen_bondhoo_ring(filename):
    """Pleasant harmonic celesta/marimba triad sequence, repeated pulse."""
    total_time = 3.2
    total_samples = int(SAMPLE_RATE * total_time)
    samples = [0.0] * total_samples
    
    # 2 ring bursts: at 0.0s and 1.2s
    burst_starts = [0.0, 1.2]
    # Arpeggio notes: C5 (523.25), E5 (659.25), G5 (783.99), B5 (987.77)
    notes = [
        (0.00, 523.25, 0.40),
        (0.12, 659.25, 0.40),
        (0.24, 783.99, 0.45),
        (0.38, 987.77, 0.55),
        (0.55, 1046.50, 0.50),
    ]
    
    for burst in burst_starts:
        for offset, freq, dur in notes:
            start_t = burst + offset
            start_idx = int(start_t * SAMPLE_RATE)
            note_samples = int(dur * SAMPLE_RATE)
            for i in range(note_samples):
                idx = start_idx + i
                if idx >= total_samples:
                    break
                t = i / SAMPLE_RATE
                env = envelope(t, 0.015, 4.0, dur)
                # Fundamental + soft 2nd and 3rd harmonics for warm marimba/celesta timbre
                val = 0.55 * math.sin(2 * math.pi * freq * t) + \
                      0.30 * math.sin(2 * math.pi * freq * 2 * t) * math.exp(-6 * t) + \
                      0.15 * math.sin(2 * math.pi * freq * 3 * t) * math.exp(-9 * t)
                samples[idx] += val * env * 0.45
                
    create_wav(filename, samples)

def gen_digital_bell(filename):
    """Crisp modern electronic dual-tone ring cadence."""
    total_time = 2.8
    total_samples = int(SAMPLE_RATE * total_time)
    samples = [0.0] * total_samples
    
    # Dual ringing tones: 784Hz + 880Hz with warble
    pulses = [0.0, 0.25, 0.50, 1.2, 1.45, 1.70]
    dur = 0.18
    
    for start_t in pulses:
        start_idx = int(start_t * SAMPLE_RATE)
        p_samples = int(dur * SAMPLE_RATE)
        for i in range(p_samples):
            idx = start_idx + i
            if idx >= total_samples:
                break
            t = i / SAMPLE_RATE
            env = envelope(t, 0.01, 3.5, dur)
            # Dual frequency FM phone bell
            val = 0.5 * math.sin(2 * math.pi * 784 * t) + \
                  0.5 * math.sin(2 * math.pi * 880 * t)
            samples[idx] += val * env * 0.4
            
    create_wav(filename, samples)

def gen_celesta_chime(filename):
    """Soft melodic harp/chime cascade for incoming call."""
    total_time = 3.0
    total_samples = int(SAMPLE_RATE * total_time)
    samples = [0.0] * total_samples
    
    # Ascending then descending soft chimes
    chimes = [
        (0.00, 440.0, 0.6),
        (0.18, 554.37, 0.6),
        (0.36, 659.25, 0.6),
        (0.54, 880.0, 0.8),
        (1.40, 659.25, 0.6),
        (1.58, 880.0, 0.7),
        (1.76, 1108.73, 0.9),
    ]
    
    for offset, freq, dur in chimes:
        start_idx = int(offset * SAMPLE_RATE)
        dur_samples = int(dur * SAMPLE_RATE)
        for i in range(dur_samples):
            idx = start_idx + i
            if idx >= total_samples:
                break
            t = i / SAMPLE_RATE
            env = envelope(t, 0.02, 3.0, dur)
            val = 0.7 * math.sin(2 * math.pi * freq * t) + \
                  0.3 * math.sin(2 * math.pi * freq * 2 * t) * math.exp(-4 * t)
            samples[idx] += val * env * 0.4
            
    create_wav(filename, samples)

# -------------------------------------------------------------
# 2. INCOMING MESSAGE TONES (~150ms - 250ms)
# -------------------------------------------------------------

def gen_bubble_pop(filename):
    """Crisp, sweet upward resonant bubble pop for incoming message."""
    total_time = 0.18
    total_samples = int(SAMPLE_RATE * total_time)
    samples = [0.0] * total_samples
    
    # Rapid pitch sweep: 480Hz -> 960Hz
    for i in range(total_samples):
        t = i / SAMPLE_RATE
        freq = 480 + 480 * (t / total_time) ** 0.6
        env = envelope(t, 0.008, 6.0, total_time)
        val = math.sin(2 * math.pi * freq * t) + \
              0.25 * math.sin(2 * math.pi * freq * 2 * t) * math.exp(-12 * t)
        samples[i] = val * env * 0.55
        
    create_wav(filename, samples)

def gen_clear_ding(filename):
    """Crystalline pure high bell ding (C6: 1046.5Hz)."""
    total_time = 0.25
    total_samples = int(SAMPLE_RATE * total_time)
    samples = [0.0] * total_samples
    
    freq = 1046.5
    for i in range(total_samples):
        t = i / SAMPLE_RATE
        env = envelope(t, 0.005, 5.0, total_time)
        val = 0.75 * math.sin(2 * math.pi * freq * t) + \
              0.20 * math.sin(2 * math.pi * freq * 2 * t) * math.exp(-8 * t) + \
              0.05 * math.sin(2 * math.pi * freq * 3 * t) * math.exp(-14 * t)
        samples[i] = val * env * 0.5
        
    create_wav(filename, samples)

def gen_soft_chime(filename):
    """Smooth modern two-tone incoming chime (F5 -> A5)."""
    total_time = 0.22
    total_samples = int(SAMPLE_RATE * total_time)
    samples = [0.0] * total_samples
    
    tones = [
        (0.00, 698.46, 0.12),
        (0.08, 880.00, 0.14),
    ]
    
    for offset, freq, dur in tones:
        start_idx = int(offset * SAMPLE_RATE)
        dur_samples = int(dur * SAMPLE_RATE)
        for i in range(dur_samples):
            idx = start_idx + i
            if idx >= total_samples:
                break
            t = i / SAMPLE_RATE
            env = envelope(t, 0.01, 4.5, dur)
            val = math.sin(2 * math.pi * freq * t) + \
                  0.2 * math.sin(2 * math.pi * freq * 2 * t) * math.exp(-6 * t)
            samples[idx] += val * env * 0.45
            
    create_wav(filename, samples)

# -------------------------------------------------------------
# 3. OUTGOING MESSAGE SENT TONES (~70ms - 150ms)
# -------------------------------------------------------------

def gen_subtle_sent(filename):
    """Subtle warm upward blip confirmation."""
    total_time = 0.12
    total_samples = int(SAMPLE_RATE * total_time)
    samples = [0.0] * total_samples
    
    for i in range(total_samples):
        t = i / SAMPLE_RATE
        freq = 440 + 260 * (t / total_time)
        env = envelope(t, 0.006, 7.0, total_time)
        val = math.sin(2 * math.pi * freq * t)
        samples[i] = val * env * 0.4
        
    create_wav(filename, samples)

def gen_soft_swoosh(filename):
    """Airy soft whoosh confirming message dispatch."""
    total_time = 0.15
    total_samples = int(SAMPLE_RATE * total_time)
    samples = [0.0] * total_samples
    
    # White noise + gentle upward sine sweep
    import random
    random.seed(42)
    for i in range(total_samples):
        t = i / SAMPLE_RATE
        env = envelope(t, 0.02, 5.0, total_time)
        sine_freq = 320 + 380 * (t / total_time)
        noise = (random.random() * 2 - 1) * 0.25 * math.exp(-6 * t)
        val = 0.75 * math.sin(2 * math.pi * sine_freq * t) + noise
        samples[i] = val * env * 0.35
        
    create_wav(filename, samples)

def gen_quick_click(filename):
    """Tactile crisp modern mechanical confirmation tap."""
    total_time = 0.08
    total_samples = int(SAMPLE_RATE * total_time)
    samples = [0.0] * total_samples
    
    for i in range(total_samples):
        t = i / SAMPLE_RATE
        freq = 1100 - 400 * (t / total_time)
        env = envelope(t, 0.003, 14.0, total_time)
        val = math.sin(2 * math.pi * freq * t)
        samples[i] = val * env * 0.45
        
    create_wav(filename, samples)

if __name__ == '__main__':
    base_dir = os.path.join(os.path.dirname(os.path.dirname(os.path.abspath(__file__))), 'public', 'sounds', 'messenger')
    print(f"Generating audio library in {base_dir}...")
    
    # 1. Calls
    gen_bondhoo_ring(os.path.join(base_dir, 'bondhoo_ring.wav'))
    gen_digital_bell(os.path.join(base_dir, 'digital_bell.wav'))
    gen_celesta_chime(os.path.join(base_dir, 'celesta_chime.wav'))
    
    # 2. Incoming Messages
    gen_bubble_pop(os.path.join(base_dir, 'bubble_pop.wav'))
    gen_clear_ding(os.path.join(base_dir, 'clear_ding.wav'))
    gen_soft_chime(os.path.join(base_dir, 'soft_chime.wav'))
    
    # 3. Outgoing Messages
    gen_subtle_sent(os.path.join(base_dir, 'subtle_sent.wav'))
    gen_soft_swoosh(os.path.join(base_dir, 'soft_swoosh.wav'))
    gen_quick_click(os.path.join(base_dir, 'quick_click.wav'))
    
    print("All 9 audio assets generated successfully!")
