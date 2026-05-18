"""
Hardware Device Fingerprint — cross-platform unique device ID.
Used to identify the machine during license activation.
"""

import hashlib
import platform
import subprocess
import uuid


def _get_cpu_id() -> str:
    """Attempt to get a CPU serial or CPUID string."""
    system = platform.system()
    try:
        if system == "Windows":
            out = subprocess.check_output(
                "wmic cpu get processorid", shell=True
            ).decode().strip()
            lines = [l.strip() for l in out.splitlines() if l.strip() and l.strip() != "ProcessorId"]
            return lines[0] if lines else ""
        elif system == "Darwin":
            out = subprocess.check_output(
                ["system_profiler", "SPHardwareDataType"]
            ).decode()
            for line in out.splitlines():
                if "Serial Number" in line:
                    return line.split(":")[-1].strip()
        elif system == "Linux":
            try:
                with open("/proc/cpuinfo") as f:
                    for line in f:
                        if "Serial" in line:
                            return line.split(":")[-1].strip()
            except Exception:
                pass
    except Exception:
        pass
    return ""


def _get_mac_address() -> str:
    """Get the primary network interface MAC address."""
    mac = uuid.getnode()
    return ":".join(f"{(mac >> (i * 8)) & 0xFF:02x}" for i in range(5, -1, -1))


def get_hardware_fingerprint() -> str:
    """
    Compute a stable SHA-256 fingerprint from CPU ID + MAC address.
    Stable across reboots, changes if hardware changes significantly.
    """
    cpu_id = _get_cpu_id()
    mac    = _get_mac_address()
    raw    = f"{cpu_id}|{mac}|{platform.node()}"
    return hashlib.sha256(raw.encode()).hexdigest()


def get_device_name() -> str:
    return platform.node()


def get_os_name() -> str:
    system = platform.system().lower()
    if system == "darwin":
        return "mac"
    return system  # windows | linux
