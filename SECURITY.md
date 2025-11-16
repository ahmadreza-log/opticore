# Security Policy 🔒

> We take stability and security seriously. This document explains what is supported and how to contact us if you find a vulnerability.

## ✅ Supported Versions

We strive to keep OptiCore secure on the latest release. Security fixes will generally target the most recent tagged version. If you require long-term support for prior releases, please open a discussion to coordinate maintenance.

| Version | Supported |
|---------|-----------|
| 1.x     | ✅        |
| < 1.0   | ❌        |

## 📣 Reporting a Vulnerability

Please report suspected vulnerabilities privately. Email **`security@opticore.dev`** with the following details:

- Description of the vulnerability and impact.
- Steps to reproduce (including configuration and sample data if applicable).
- Any proofs of concept, log snippets, or screenshots.
- Recommended remediation or mitigation ideas.

We aim to acknowledge reports within **3 business days** and provide a status update at least every **7 days** until the issue is resolved.

## 🛡️ Defence in Depth

In addition to fixing vulnerabilities as they are reported, OptiCore follows some basic hardening practices:

- Conditional loading of optimisation snippets based on saved settings.
- Avoiding direct script access where possible and adding `index.php` guards in all plugin directories.
- Keeping internal helper functions small and well-documented to simplify audits.

These measures are not a replacement for proper server hardening or a Web Application Firewall, but they help reduce accidental exposure.

## 🔁 Disclosure Process

1. We will investigate, confirm the issue, and work on a fix.
2. You will receive updates on progress and timelines.
3. Once resolved, we will release an update and credit you (with permission) in the changelog or advisory.
4. We kindly ask that you do not publicly disclose the vulnerability until a patch is available and users have had reasonable time to update.

Thank you for helping us keep OptiCore secure!


