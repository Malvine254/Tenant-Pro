package com.tenantpro.app.data.local.dao;

import android.database.Cursor;
import android.os.CancellationSignal;
import androidx.annotation.NonNull;
import androidx.room.CoroutinesRoom;
import androidx.room.EntityInsertionAdapter;
import androidx.room.RoomDatabase;
import androidx.room.RoomSQLiteQuery;
import androidx.room.SharedSQLiteStatement;
import androidx.room.util.CursorUtil;
import androidx.room.util.DBUtil;
import androidx.sqlite.db.SupportSQLiteStatement;
import com.tenantpro.app.data.local.entity.InvoiceEntity;
import java.lang.Class;
import java.lang.Exception;
import java.lang.Object;
import java.lang.Override;
import java.lang.String;
import java.lang.SuppressWarnings;
import java.util.ArrayList;
import java.util.Collections;
import java.util.List;
import java.util.concurrent.Callable;
import javax.annotation.processing.Generated;
import kotlin.Unit;
import kotlin.coroutines.Continuation;

@Generated("androidx.room.RoomProcessor")
@SuppressWarnings({"unchecked", "deprecation"})
public final class InvoiceDao_Impl implements InvoiceDao {
  private final RoomDatabase __db;

  private final EntityInsertionAdapter<InvoiceEntity> __insertionAdapterOfInvoiceEntity;

  private final SharedSQLiteStatement __preparedStmtOfClearAll;

  public InvoiceDao_Impl(@NonNull final RoomDatabase __db) {
    this.__db = __db;
    this.__insertionAdapterOfInvoiceEntity = new EntityInsertionAdapter<InvoiceEntity>(__db) {
      @Override
      @NonNull
      protected String createQuery() {
        return "INSERT OR REPLACE INTO `invoices` (`id`,`billingType`,`status`,`totalAmount`,`paidAmount`,`dueDate`,`billingPeriod`,`description`,`unitId`,`unitName`,`propertyId`,`propertyName`,`createdAt`,`cachedAt`) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
      }

      @Override
      protected void bind(@NonNull final SupportSQLiteStatement statement,
          @NonNull final InvoiceEntity entity) {
        statement.bindString(1, entity.getId());
        statement.bindString(2, entity.getBillingType());
        statement.bindString(3, entity.getStatus());
        statement.bindDouble(4, entity.getTotalAmount());
        statement.bindDouble(5, entity.getPaidAmount());
        if (entity.getDueDate() == null) {
          statement.bindNull(6);
        } else {
          statement.bindString(6, entity.getDueDate());
        }
        if (entity.getBillingPeriod() == null) {
          statement.bindNull(7);
        } else {
          statement.bindString(7, entity.getBillingPeriod());
        }
        if (entity.getDescription() == null) {
          statement.bindNull(8);
        } else {
          statement.bindString(8, entity.getDescription());
        }
        if (entity.getUnitId() == null) {
          statement.bindNull(9);
        } else {
          statement.bindString(9, entity.getUnitId());
        }
        if (entity.getUnitName() == null) {
          statement.bindNull(10);
        } else {
          statement.bindString(10, entity.getUnitName());
        }
        if (entity.getPropertyId() == null) {
          statement.bindNull(11);
        } else {
          statement.bindString(11, entity.getPropertyId());
        }
        if (entity.getPropertyName() == null) {
          statement.bindNull(12);
        } else {
          statement.bindString(12, entity.getPropertyName());
        }
        statement.bindString(13, entity.getCreatedAt());
        statement.bindLong(14, entity.getCachedAt());
      }
    };
    this.__preparedStmtOfClearAll = new SharedSQLiteStatement(__db) {
      @Override
      @NonNull
      public String createQuery() {
        final String _query = "DELETE FROM invoices";
        return _query;
      }
    };
  }

  @Override
  public Object insertAll(final List<InvoiceEntity> invoices,
      final Continuation<? super Unit> $completion) {
    return CoroutinesRoom.execute(__db, true, new Callable<Unit>() {
      @Override
      @NonNull
      public Unit call() throws Exception {
        __db.beginTransaction();
        try {
          __insertionAdapterOfInvoiceEntity.insert(invoices);
          __db.setTransactionSuccessful();
          return Unit.INSTANCE;
        } finally {
          __db.endTransaction();
        }
      }
    }, $completion);
  }

  @Override
  public Object clearAll(final Continuation<? super Unit> $completion) {
    return CoroutinesRoom.execute(__db, true, new Callable<Unit>() {
      @Override
      @NonNull
      public Unit call() throws Exception {
        final SupportSQLiteStatement _stmt = __preparedStmtOfClearAll.acquire();
        try {
          __db.beginTransaction();
          try {
            _stmt.executeUpdateDelete();
            __db.setTransactionSuccessful();
            return Unit.INSTANCE;
          } finally {
            __db.endTransaction();
          }
        } finally {
          __preparedStmtOfClearAll.release(_stmt);
        }
      }
    }, $completion);
  }

  @Override
  public Object getAllInvoices(final Continuation<? super List<InvoiceEntity>> $completion) {
    final String _sql = "SELECT * FROM invoices ORDER BY createdAt DESC";
    final RoomSQLiteQuery _statement = RoomSQLiteQuery.acquire(_sql, 0);
    final CancellationSignal _cancellationSignal = DBUtil.createCancellationSignal();
    return CoroutinesRoom.execute(__db, false, _cancellationSignal, new Callable<List<InvoiceEntity>>() {
      @Override
      @NonNull
      public List<InvoiceEntity> call() throws Exception {
        final Cursor _cursor = DBUtil.query(__db, _statement, false, null);
        try {
          final int _cursorIndexOfId = CursorUtil.getColumnIndexOrThrow(_cursor, "id");
          final int _cursorIndexOfBillingType = CursorUtil.getColumnIndexOrThrow(_cursor, "billingType");
          final int _cursorIndexOfStatus = CursorUtil.getColumnIndexOrThrow(_cursor, "status");
          final int _cursorIndexOfTotalAmount = CursorUtil.getColumnIndexOrThrow(_cursor, "totalAmount");
          final int _cursorIndexOfPaidAmount = CursorUtil.getColumnIndexOrThrow(_cursor, "paidAmount");
          final int _cursorIndexOfDueDate = CursorUtil.getColumnIndexOrThrow(_cursor, "dueDate");
          final int _cursorIndexOfBillingPeriod = CursorUtil.getColumnIndexOrThrow(_cursor, "billingPeriod");
          final int _cursorIndexOfDescription = CursorUtil.getColumnIndexOrThrow(_cursor, "description");
          final int _cursorIndexOfUnitId = CursorUtil.getColumnIndexOrThrow(_cursor, "unitId");
          final int _cursorIndexOfUnitName = CursorUtil.getColumnIndexOrThrow(_cursor, "unitName");
          final int _cursorIndexOfPropertyId = CursorUtil.getColumnIndexOrThrow(_cursor, "propertyId");
          final int _cursorIndexOfPropertyName = CursorUtil.getColumnIndexOrThrow(_cursor, "propertyName");
          final int _cursorIndexOfCreatedAt = CursorUtil.getColumnIndexOrThrow(_cursor, "createdAt");
          final int _cursorIndexOfCachedAt = CursorUtil.getColumnIndexOrThrow(_cursor, "cachedAt");
          final List<InvoiceEntity> _result = new ArrayList<InvoiceEntity>(_cursor.getCount());
          while (_cursor.moveToNext()) {
            final InvoiceEntity _item;
            final String _tmpId;
            _tmpId = _cursor.getString(_cursorIndexOfId);
            final String _tmpBillingType;
            _tmpBillingType = _cursor.getString(_cursorIndexOfBillingType);
            final String _tmpStatus;
            _tmpStatus = _cursor.getString(_cursorIndexOfStatus);
            final double _tmpTotalAmount;
            _tmpTotalAmount = _cursor.getDouble(_cursorIndexOfTotalAmount);
            final double _tmpPaidAmount;
            _tmpPaidAmount = _cursor.getDouble(_cursorIndexOfPaidAmount);
            final String _tmpDueDate;
            if (_cursor.isNull(_cursorIndexOfDueDate)) {
              _tmpDueDate = null;
            } else {
              _tmpDueDate = _cursor.getString(_cursorIndexOfDueDate);
            }
            final String _tmpBillingPeriod;
            if (_cursor.isNull(_cursorIndexOfBillingPeriod)) {
              _tmpBillingPeriod = null;
            } else {
              _tmpBillingPeriod = _cursor.getString(_cursorIndexOfBillingPeriod);
            }
            final String _tmpDescription;
            if (_cursor.isNull(_cursorIndexOfDescription)) {
              _tmpDescription = null;
            } else {
              _tmpDescription = _cursor.getString(_cursorIndexOfDescription);
            }
            final String _tmpUnitId;
            if (_cursor.isNull(_cursorIndexOfUnitId)) {
              _tmpUnitId = null;
            } else {
              _tmpUnitId = _cursor.getString(_cursorIndexOfUnitId);
            }
            final String _tmpUnitName;
            if (_cursor.isNull(_cursorIndexOfUnitName)) {
              _tmpUnitName = null;
            } else {
              _tmpUnitName = _cursor.getString(_cursorIndexOfUnitName);
            }
            final String _tmpPropertyId;
            if (_cursor.isNull(_cursorIndexOfPropertyId)) {
              _tmpPropertyId = null;
            } else {
              _tmpPropertyId = _cursor.getString(_cursorIndexOfPropertyId);
            }
            final String _tmpPropertyName;
            if (_cursor.isNull(_cursorIndexOfPropertyName)) {
              _tmpPropertyName = null;
            } else {
              _tmpPropertyName = _cursor.getString(_cursorIndexOfPropertyName);
            }
            final String _tmpCreatedAt;
            _tmpCreatedAt = _cursor.getString(_cursorIndexOfCreatedAt);
            final long _tmpCachedAt;
            _tmpCachedAt = _cursor.getLong(_cursorIndexOfCachedAt);
            _item = new InvoiceEntity(_tmpId,_tmpBillingType,_tmpStatus,_tmpTotalAmount,_tmpPaidAmount,_tmpDueDate,_tmpBillingPeriod,_tmpDescription,_tmpUnitId,_tmpUnitName,_tmpPropertyId,_tmpPropertyName,_tmpCreatedAt,_tmpCachedAt);
            _result.add(_item);
          }
          return _result;
        } finally {
          _cursor.close();
          _statement.release();
        }
      }
    }, $completion);
  }

  @NonNull
  public static List<Class<?>> getRequiredConverters() {
    return Collections.emptyList();
  }
}
