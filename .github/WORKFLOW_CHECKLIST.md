# bill-order-bundle GitHub Actions 工作流更新清单

## 更新摘要
已按照最新标准更新 `bill-order-bundle` 包的 GitHub Actions 工作流配置。

## 更新内容

### 1. phpstan.yml（PHPStan 检查）
**改进项**：
- ✅ 添加明确的 PHP 8.3 设置步骤 (`shivammathur/setup-php@v2`)
- ✅ 升级缓存 actions 从 v3 到 v4
- ✅ 改进缓存键策略（从 `php-` 改为 `composer-`）
- ✅ 添加 `--no-interaction` 标志到 composer install
- ✅ 添加 PHPStan 运行参数：`--ansi --memory-limit=512M`

**预期好处**：
- 更快的依赖安装和缓存命中
- 更清晰的错误输出 (ANSI colors)
- 改进的内存管理
- 更显式的环境配置

---

### 2. phpunit.yml（PHPUnit 测试）
**改进项**：
- ✅ 添加 `permissions` 部分以支持 OIDC token
- ✅ 升级 checkout action 从 @master 到 @v4
- ✅ 添加 `tools: composer:latest` 确保最新 composer
- ✅ 新增 Composer 缓存步骤，支持多版本 PHP
- ✅ 改进缓存键策略（版本感知）
- ✅ 移除 `--no-suggest` 标志（Composer 2+ 已弃用）
- ✅ 添加 `--no-coverage` 标志到 PHPUnit

**预期好处**：
- 多版本 PHP 的独立缓存策略
- 提高 CI/CD 安全性
- 减少不必要的覆盖率生成
- 更快的测试执行

---

### 3. quality.yml（代码质量检查）✨ 新增
**包含检查项**：
- PHP-CS-Fixer 代码风格检查
- PHPMD (PHP Mess Detector) 代码质量分析
- Enlightn 安全问题检查

**特点**：
- 所有检查使用 `continue-on-error: true` 以避免阻塞
- 缺失的工具会自动下载
- 为将来的工具集成预留扩展点

---

### 4. static-analysis.yml（静态分析）✨ 新增
**包含检查项**：
- PHPStan Level 1（生产代码）
- PHPStan Level 0（测试代码）
- PHPStan JSON 格式输出用于集成

**特点**：
- 多阶段分析策略
- 生成 JSON 报告支持后续处理
- 独立的缓存键以避免干扰

---

### 5. tests.yml（完整测试套件）✨ 新增
**包含内容**：
- 矩阵测试：PHP 8.2、8.3、8.4
- 依赖模式：stable 和 lowest
- 代码覆盖率收集 (pcov)
- Codecov 上传集成
- 覆盖率报告生成和归档

**特点**：
- `fail-fast: false` 确保所有组合都执行
- 智能缓存策略支持多种组合
- 覆盖率数据持久化用于分析

---

## 验证步骤

### 本地验证
```bash
# 验证 composer.json
cd packages/bill-order-bundle
composer validate --strict

# 检查工作流文件存在
ls -la .github/workflows/
```

### GitHub Actions 执行预期
1. **PHPStan 工作流**：PHP 8.3 静态分析，~3-5 分钟
2. **PHPUnit 工作流**：PHP 8.2/8.3/8.4 矩阵测试，~10-15 分钟
3. **代码质量工作流**：代码风格和质量检查，~5-10 分钟
4. **静态分析工作流**：多级分析，~5-8 分钟
5. **完整测试工作流**：覆盖率和完整报告，~15-20 分钟

---

## 变更摘要

| 文件 | 状态 | 主要变化 |
|------|------|---------|
| phpstan.yml | 更新 | +PHP Setup, +v4 Cache, +内存限制 |
| phpunit.yml | 更新 | +权限声明, +Cache, +工具版本控制 |
| quality.yml | 新增 | 风格+质量+安全检查 |
| static-analysis.yml | 新增 | 多层次静态分析 |
| tests.yml | 新增 | 矩阵测试+覆盖率+报告 |

---

## 后续建议

1. **首次运行**：推送到 master 或创建 PR 以验证所有工作流正常运行
2. **监控**：查看 GitHub Actions 运行日志确认各步骤成功
3. **缓存优化**：监控缓存命中率，必要时调整缓存策略
4. **覆盖率目标**：在 Codecov 设置覆盖率目标和保护规则
5. **扩展**：根据需要添加更多检查项（如 Psalm、ECS 等）

---

## 参考资源
- GitHub Actions Best Practices: https://github.com/features/actions
- PHP Actions: https://github.com/shivammathur/setup-php
- Composer Actions: https://getcomposer.org
