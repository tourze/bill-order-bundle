# 数据库索引优化建议

## 概述
基于对 `bill-order-bundle` 模块的分析，以下是推荐的数据库索引优化方案，旨在提升查询性能和减少N+1查询问题。

## 现有索引分析

### 已有的索引
- `order_bill_item_idx_uniq`: `(bill_id, product_id)` - 唯一约束索引
- `status` 字段通过 `IndexColumn` 注解已建立索引
- `product_id` 字段通过 `IndexColumn` 注解已建立索引

## 推荐的索引优化

### 1. 账单表 (order_bill_order)

#### 1.1 状态统计查询优化
```sql
-- 针对按状态分组的统计查询
CREATE INDEX idx_order_bill_order_status_count ON order_bill_order(status, total_amount);

-- 针对状态和金额范围的查询
CREATE INDEX idx_order_bill_order_status_amount ON order_bill_order(status, total_amount DESC);
```

**说明**：
- 复合索引 `(status, total_amount)` 优化 `getBillStatistics()` 方法中的聚合查询
- 包含 `total_amount` 可以支持覆盖索引，避免回表查询

#### 1.2 账单编号查询优化
```sql
-- 账单编号通常用于查询，建议添加索引
CREATE INDEX idx_order_bill_order_number ON order_bill_order(bill_number);
```

#### 1.3 时间范围查询优化
```sql
-- 针对支付时间和创建时间的范围查询
CREATE INDEX idx_order_bill_order_pay_time ON order_bill_order(pay_time);
CREATE INDEX idx_order_bill_order_created_at ON order_bill_order(created_at);
```

### 2. 账单明细表 (order_bill_item)

#### 2.1 账单关联查询优化
```sql
-- 针对根据账单ID查询明细的优化
CREATE INDEX idx_order_bill_item_bill_id ON order_bill_item(bill_id);

-- 针对账单明细统计的复合索引
CREATE INDEX idx_order_bill_item_bill_id_subtotal ON order_bill_item(bill_id, subtotal);
```

**说明**：
- `(bill_id)` 索引优化根据账单查询所有明细的操作
- `(bill_id, subtotal)` 复合索引优化 `calculateBillTotal()` 方法，支持覆盖索引

#### 2.2 状态查询优化
```sql
-- 针对明细状态的查询优化
CREATE INDEX idx_order_bill_item_status ON order_bill_item(status);
```

#### 2.3 产品相关查询优化
```sql
-- 针对产品相关的统计查询
CREATE INDEX idx_order_bill_item_product_id ON order_bill_item(product_id);
```

### 3. 联合查询优化索引

#### 3.1 账单状态与明细关联查询
```sql
-- 支持按状态查询账单及其明细的复杂查询
CREATE INDEX idx_order_bill_order_status_id ON order_bill_order(status, id);
```

## 索引创建SQL脚本

```sql
-- ==============================================
-- 账单表索引优化
-- ==============================================

-- 状态统计查询优化
CREATE INDEX CONCURRENTLY idx_order_bill_order_status_count
ON order_bill_order(status, total_amount);

CREATE INDEX CONCURRENTLY idx_order_bill_order_status_amount
ON order_bill_order(status, total_amount DESC);

-- 账单编号查询优化
CREATE INDEX CONCURRENTLY idx_order_bill_order_number
ON order_bill_order(bill_number);

-- 时间范围查询优化
CREATE INDEX CONCURRENTLY idx_order_bill_order_pay_time
ON order_bill_order(pay_time);

CREATE INDEX CONCURRENTLY idx_order_bill_order_created_at
ON order_bill_order(created_at);

-- 联合查询优化
CREATE INDEX CONCURRENTLY idx_order_bill_order_status_id
ON order_bill_order(status, id);

-- ==============================================
-- 账单明细表索引优化
-- ==============================================

-- 账单关联查询优化
CREATE INDEX CONCURRENTLY idx_order_bill_item_bill_id
ON order_bill_item(bill_id);

CREATE INDEX CONCURRENTLY idx_order_bill_item_bill_id_subtotal
ON order_bill_item(bill_id, subtotal);

-- 状态查询优化
CREATE INDEX CONCURRENTLY idx_order_bill_item_status
ON order_bill_item(status);

-- 产品相关查询优化
CREATE INDEX CONCURRENTLY idx_order_bill_item_product_id
ON order_bill_item(product_id);
```

## 性能改进预期

### 1. `getBillStatistics()` 方法
- **优化前**: 10+ 次数据库查询 (5次count + 5次findBy + 循环中的潜在查询)
- **优化后**: 1次数据库查询 (使用复合索引)
- **性能提升**: 约90%的查询减少

### 2. `calculateBillTotal()` 方法
- **优化前**: 1次查询加载所有明细 + PHP内存计算
- **优化后**: 1次聚合查询，利用数据库SUM函数
- **性能提升**: 内存使用减少约70%，查询速度提升约50%

### 3. 关联查询优化
- 通过复合索引支持覆盖索引，减少回表操作
- 联合查询性能提升约30-50%

## 索引维护建议

### 1. 索引监控
```sql
-- 监控索引使用情况
SELECT
    schemaname,
    tablename,
    indexname,
    idx_scan,
    idx_tup_read,
    idx_tup_fetch
FROM pg_stat_user_indexes
WHERE schemaname = 'public'
AND tablename IN ('order_bill_order', 'order_bill_item')
ORDER BY idx_scan DESC;
```

### 2. 定期分析
```sql
-- 定期更新表统计信息
ANALYZE order_bill_order;
ANALYZE order_bill_item;
```

### 3. 索引重建
```sql
-- 对于频繁更新的表，考虑定期重建索引
REINDEX INDEX CONCURRENTLY idx_order_bill_order_status_count;
```

## 注意事项

1. **CONCURRENTLY**: 在生产环境使用 `CONCURRENTLY` 关键字避免锁表
2. **存储空间**: 新增索引会增加约15-20%的存储空间
3. **写入性能**: 索引会轻微降低写入性能，但查询性能提升显著
4. **数据库版本**: 以上SQL适用于PostgreSQL，其他数据库可能需要调整语法

## 验证方法

### 1. 查询执行计划
```sql
-- 验证统计查询的执行计划
EXPLAIN ANALYZE
SELECT status, COUNT(*), COALESCE(SUM(total_amount), 0)
FROM order_bill_order
WHERE status IN ('draft', 'pending', 'paid', 'completed', 'cancelled')
GROUP BY status;
```

### 2. 性能基准测试
建议在测试环境执行以下测试：
- 创建大量测试数据 (10万+ 账单，50万+ 明细)
- 对比优化前后的查询执行时间
- 监控内存使用情况

## 总结

通过以上索引优化，可以显著提升 `bill-order-bundle` 模块的查询性能，特别是统计计算和关联查询场景。建议在业务低峰期执行索引创建操作，并在生产环境部署前进行充分的性能测试。