const {
  PrismaClient,
  RoleName,
  UnitStatus,
  BillingType,
  InvoiceStatus,
  PaymentStatus,
  TransactionType,
  MaintenancePriority,
  MaintenanceStatus,
  InvitationStatus,
} = require('@prisma/client')
const bcrypt = require('bcryptjs')

const prisma = new PrismaClient()

async function main() {
  const now = new Date()

  const dateMonthsAgo = (monthsAgo, day = 5) =>
    new Date(now.getFullYear(), now.getMonth() - monthsAgo, day, 10, 0, 0, 0)

  const issueDateForMonth = (monthsAgo) => dateMonthsAgo(monthsAgo, 1)
  const dueDateForMonth = (monthsAgo) => dateMonthsAgo(monthsAgo, 7)
  const paidDateForMonth = (monthsAgo) => dateMonthsAgo(monthsAgo, 6)

  const roles = [
    { name: RoleName.LANDLORD, description: 'Property owner/manager' },
    { name: RoleName.TENANT, description: 'Occupant and bill payer' },
    { name: RoleName.ADMIN, description: 'System administrator' },
    { name: RoleName.CARETAKER, description: 'Assigned property caretaker' },
  ]

  for (const role of roles) {
    await prisma.role.upsert({
      where: { name: role.name },
      update: { description: role.description },
      create: role,
    })
  }

  await prisma.transaction.deleteMany()
  await prisma.payment.deleteMany()
  await prisma.invoice.deleteMany()
  await prisma.maintenanceRequest.deleteMany()
  await prisma.invitation.deleteMany()
  await prisma.tenant.deleteMany()
  await prisma.unit.deleteMany()
  await prisma.property.deleteMany()
  await prisma.user.deleteMany()

  const landlordRole = await prisma.role.findUniqueOrThrow({
    where: { name: RoleName.LANDLORD },
  })

  const landlordPhone = process.env.SEED_LANDLORD_PHONE || '+254700000001'
  const landlordEmail = process.env.SEED_LANDLORD_EMAIL || 'landlord@example.com'
  const landlordPassword = process.env.SEED_LANDLORD_PASSWORD || 'Pass@1234'
  const passwordHash = await bcrypt.hash(landlordPassword, 10)

  const adminRole = await prisma.role.findUniqueOrThrow({
    where: { name: RoleName.ADMIN },
  })

  const adminPhone = process.env.SEED_ADMIN_PHONE || '+254700000099'
  const adminEmail = process.env.SEED_ADMIN_EMAIL || 'admin@example.com'
  const adminPassword = process.env.SEED_ADMIN_PASSWORD || 'Admin@1234'
  const adminPasswordHash = await bcrypt.hash(adminPassword, 10)

  const tenantRole = await prisma.role.findUniqueOrThrow({
    where: { name: RoleName.TENANT },
  })

  const caretakerRole = await prisma.role.findUniqueOrThrow({
    where: { name: RoleName.CARETAKER },
  })

  const tenantPhone = process.env.SEED_TENANT_PHONE || '+254700000010'
  const tenantEmail = process.env.SEED_TENANT_EMAIL || 'tenant@example.com'
  const tenantPassword = process.env.SEED_TENANT_PASSWORD || 'Tenant@1234'
  const tenantPasswordHash = await bcrypt.hash(tenantPassword, 10)

  const caretakerPhone = process.env.SEED_CARETAKER_PHONE || '+254700000020'
  const caretakerEmail = process.env.SEED_CARETAKER_EMAIL || 'caretaker@example.com'
  const caretakerPassword = process.env.SEED_CARETAKER_PASSWORD || 'Caretaker@1234'
  const caretakerPasswordHash = await bcrypt.hash(caretakerPassword, 10)

  const usersToCreate = [
    {
      phoneNumber: adminPhone,
      email: adminEmail,
      firstName: 'System',
      lastName: 'Admin',
      roleId: adminRole.id,
      passwordHash: adminPasswordHash,
      isActive: true,
    },
    {
      phoneNumber: '+254700000098',
      email: 'ops-admin@example.com',
      firstName: 'Operations',
      lastName: 'Admin',
      roleId: adminRole.id,
      passwordHash: adminPasswordHash,
      isActive: true,
    },
    {
      phoneNumber: landlordPhone,
      email: landlordEmail,
      firstName: 'Sample',
      lastName: 'Landlord',
      roleId: landlordRole.id,
      passwordHash,
      isActive: true,
    },
    {
      phoneNumber: '+254700000002',
      email: 'landlord2@example.com',
      firstName: 'Grace',
      lastName: 'Mwangi',
      roleId: landlordRole.id,
      passwordHash,
      isActive: true,
    },
    {
      phoneNumber: '+254700000003',
      email: 'landlord3@example.com',
      firstName: 'David',
      lastName: 'Otieno',
      roleId: landlordRole.id,
      passwordHash,
      isActive: false,
    },
    {
      phoneNumber: caretakerPhone,
      email: caretakerEmail,
      firstName: 'Sample',
      lastName: 'Caretaker',
      roleId: caretakerRole.id,
      passwordHash: caretakerPasswordHash,
      isActive: true,
    },
    {
      phoneNumber: '+254700000021',
      email: 'caretaker2@example.com',
      firstName: 'James',
      lastName: 'Njoroge',
      roleId: caretakerRole.id,
      passwordHash: caretakerPasswordHash,
      isActive: true,
    },
    {
      phoneNumber: '+254700000022',
      email: 'caretaker3@example.com',
      firstName: 'Faith',
      lastName: 'Akinyi',
      roleId: caretakerRole.id,
      passwordHash: caretakerPasswordHash,
      isActive: false,
    },
    {
      phoneNumber: tenantPhone,
      email: tenantEmail,
      firstName: 'Sample',
      lastName: 'Tenant',
      roleId: tenantRole.id,
      passwordHash: tenantPasswordHash,
      isActive: true,
    },
    {
      phoneNumber: '+254700000011',
      email: 'tenant2@example.com',
      firstName: 'Brian',
      lastName: 'Kariuki',
      roleId: tenantRole.id,
      passwordHash: tenantPasswordHash,
      isActive: true,
    },
    {
      phoneNumber: '+254700000012',
      email: 'tenant3@example.com',
      firstName: 'Anne',
      lastName: 'Wambui',
      roleId: tenantRole.id,
      passwordHash: tenantPasswordHash,
      isActive: true,
    },
    {
      phoneNumber: '+254700000013',
      email: 'tenant4@example.com',
      firstName: 'Peter',
      lastName: 'Kamau',
      roleId: tenantRole.id,
      passwordHash: tenantPasswordHash,
      isActive: true,
    },
    {
      phoneNumber: '+254700000014',
      email: 'tenant5@example.com',
      firstName: 'Lilian',
      lastName: 'Atieno',
      roleId: tenantRole.id,
      passwordHash: tenantPasswordHash,
      isActive: true,
    },
    {
      phoneNumber: '+254700000015',
      email: 'tenant6@example.com',
      firstName: 'Kevin',
      lastName: 'Mutiso',
      roleId: tenantRole.id,
      passwordHash: tenantPasswordHash,
      isActive: true,
    },
    {
      phoneNumber: '+254700000016',
      email: 'tenant7@example.com',
      firstName: 'Naomi',
      lastName: 'Achieng',
      roleId: tenantRole.id,
      passwordHash: tenantPasswordHash,
      isActive: true,
    },
    {
      phoneNumber: '+254700000017',
      email: 'tenant8@example.com',
      firstName: 'John',
      lastName: 'Maina',
      roleId: tenantRole.id,
      passwordHash: tenantPasswordHash,
      isActive: false,
    },
  ]

  const createdUsers = {}

  for (const user of usersToCreate) {
    const created = await prisma.user.create({ data: user })
    createdUsers[user.phoneNumber] = created
  }

  const landlordA = createdUsers[landlordPhone]
  const landlordB = createdUsers['+254700000002']
  const caretakerA = createdUsers[caretakerPhone]
  const caretakerB = createdUsers['+254700000021']

  const riverside = await prisma.property.create({
    data: {
      landlordId: landlordA.id,
      name: 'Riverside Heights',
      description: 'Modern apartment block with secure access and rooftop amenities.',
      coverImageUrl:
        'https://images.unsplash.com/photo-1484154218962-a197022b5858?auto=format&fit=crop&w=1200&q=80',
      addressLine: '12 Riverside Drive',
      city: 'Nairobi',
      state: 'Nairobi County',
      country: 'Kenya',
    },
  })

  const sunset = await prisma.property.create({
    data: {
      landlordId: landlordA.id,
      name: 'Sunset Residency',
      description: 'Family-friendly units with ample parking and borehole water backup.',
      coverImageUrl:
        'https://images.unsplash.com/photo-1472224371017-08207f84aaae?auto=format&fit=crop&w=1200&q=80',
      addressLine: '34 Ngong Road',
      city: 'Nairobi',
      state: 'Nairobi County',
      country: 'Kenya',
    },
  })

  const greenview = await prisma.property.create({
    data: {
      landlordId: landlordB.id,
      name: 'Greenview Court',
      description: 'Quiet gated compound close to schools and shopping center.',
      coverImageUrl:
        'https://images.unsplash.com/photo-1494526585095-c41746248156?auto=format&fit=crop&w=1200&q=80',
      addressLine: '7 Thika Highway Service Lane',
      city: 'Kiambu',
      state: 'Kiambu County',
      country: 'Kenya',
    },
  })

  const unitDefinitions = [
    { propertyId: riverside.id, unitNumber: 'A101', floor: '1', rentAmount: 25000, status: UnitStatus.OCCUPIED },
    { propertyId: riverside.id, unitNumber: 'A102', floor: '1', rentAmount: 26000, status: UnitStatus.OCCUPIED },
    { propertyId: riverside.id, unitNumber: 'B201', floor: '2', rentAmount: 30000, status: UnitStatus.VACANT },
    { propertyId: riverside.id, unitNumber: 'B202', floor: '2', rentAmount: 30500, status: UnitStatus.MAINTENANCE },
    { propertyId: sunset.id, unitNumber: 'S01', floor: 'Ground', rentAmount: 18000, status: UnitStatus.OCCUPIED },
    { propertyId: sunset.id, unitNumber: 'S02', floor: 'Ground', rentAmount: 18500, status: UnitStatus.OCCUPIED },
    { propertyId: sunset.id, unitNumber: 'S11', floor: '1', rentAmount: 19500, status: UnitStatus.VACANT },
    { propertyId: greenview.id, unitNumber: 'G1', floor: '1', rentAmount: 22000, status: UnitStatus.OCCUPIED },
    { propertyId: greenview.id, unitNumber: 'G2', floor: '1', rentAmount: 22500, status: UnitStatus.OCCUPIED },
    { propertyId: greenview.id, unitNumber: 'G3', floor: '2', rentAmount: 23500, status: UnitStatus.VACANT },
  ]

  const unitsByNumber = {}

  for (const unit of unitDefinitions) {
    const createdUnit = await prisma.unit.create({
      data: {
        ...unit,
        imageUrls: [
          'https://images.unsplash.com/photo-1505693416388-ac5ce068fe85?auto=format&fit=crop&w=900&q=80',
          'https://images.unsplash.com/photo-1493663284031-b7e3aefcae8e?auto=format&fit=crop&w=900&q=80',
        ],
      },
    })
    unitsByNumber[createdUnit.unitNumber] = createdUnit
  }

  const tenantAssignments = [
    { phone: tenantPhone, unitNumber: 'A101', monthsAgo: 10 },
    { phone: '+254700000011', unitNumber: 'A102', monthsAgo: 8 },
    { phone: '+254700000012', unitNumber: 'S01', monthsAgo: 7 },
    { phone: '+254700000013', unitNumber: 'S02', monthsAgo: 5 },
    { phone: '+254700000014', unitNumber: 'G1', monthsAgo: 6 },
    { phone: '+254700000015', unitNumber: 'G2', monthsAgo: 4 },
  ]

  const createdTenants = {}

  for (const assignment of tenantAssignments) {
    const user = createdUsers[assignment.phone]
    const unit = unitsByNumber[assignment.unitNumber]

    const tenant = await prisma.tenant.create({
      data: {
        userId: user.id,
        unitId: unit.id,
        moveInDate: dateMonthsAgo(assignment.monthsAgo, 3),
        isActive: true,
      },
      include: {
        user: true,
        unit: true,
      },
    })

    createdTenants[assignment.phone] = tenant
  }

  const inactiveTenant = await prisma.tenant.create({
    data: {
      userId: createdUsers['+254700000017'].id,
      unitId: unitsByNumber.G3.id,
      moveInDate: dateMonthsAgo(18, 1),
      moveOutDate: dateMonthsAgo(2, 20),
      isActive: false,
    },
  })

  const invoiceRecords = []

  for (const tenant of Object.values(createdTenants)) {
    for (let monthsAgo = 5; monthsAgo >= 0; monthsAgo -= 1) {
      const baseAmount = Number(tenant.unit.rentAmount)
      const waterAmount = 1500
      const garbageAmount = 700
      const penaltyAmount = monthsAgo >= 4 ? 500 : 0

      invoiceRecords.push({
        tenant,
        billingType: BillingType.RENT,
        amount: baseAmount,
        penaltyAmount,
        issueDate: issueDateForMonth(monthsAgo),
        dueDate: dueDateForMonth(monthsAgo),
      })

      if (monthsAgo % 2 === 0) {
        invoiceRecords.push({
          tenant,
          billingType: BillingType.WATER,
          amount: waterAmount,
          penaltyAmount: 0,
          issueDate: issueDateForMonth(monthsAgo),
          dueDate: dueDateForMonth(monthsAgo),
        })
      }

      if (monthsAgo % 3 === 0) {
        invoiceRecords.push({
          tenant,
          billingType: BillingType.GARBAGE,
          amount: garbageAmount,
          penaltyAmount: 0,
          issueDate: issueDateForMonth(monthsAgo),
          dueDate: dueDateForMonth(monthsAgo),
        })
      }
    }
  }

  let successPaymentSequence = 1

  for (const [index, invoiceSpec] of invoiceRecords.entries()) {
    const periodMonth = invoiceSpec.issueDate.getMonth() + 1
    const periodYear = invoiceSpec.issueDate.getFullYear()
    const totalAmount = invoiceSpec.amount + invoiceSpec.penaltyAmount

    const isOld = index % 4 !== 0
    const isPartial = index % 7 === 0

    const status = isOld
      ? InvoiceStatus.PAID
      : isPartial
        ? InvoiceStatus.PENDING
        : InvoiceStatus.OVERDUE

    const paidAmount = status === InvoiceStatus.PAID ? totalAmount : isPartial ? totalAmount * 0.5 : 0

    const paidOn = new Date(invoiceSpec.dueDate)
    paidOn.setDate(Math.max(2, paidOn.getDate() - 1))

    const invoice = await prisma.invoice.create({
      data: {
        tenantId: invoiceSpec.tenant.id,
        userId: invoiceSpec.tenant.userId,
        unitId: invoiceSpec.tenant.unitId,
        billingType: invoiceSpec.billingType,
        periodMonth,
        periodYear,
        issueDate: invoiceSpec.issueDate,
        dueDate: invoiceSpec.dueDate,
        amount: invoiceSpec.amount,
        penaltyAmount: invoiceSpec.penaltyAmount,
        totalAmount,
        paidAmount,
        status,
        paidAt: status === InvoiceStatus.PAID ? paidOn : null,
      },
    })

    if (paidAmount > 0) {
      const paymentDate = new Date(paidOn)

      const payment = await prisma.payment.create({
        data: {
          invoiceId: invoice.id,
          tenantId: invoiceSpec.tenant.id,
          userId: invoiceSpec.tenant.userId,
          amount: paidAmount,
          method: 'MPESA',
          status: PaymentStatus.SUCCESS,
          phoneNumber: invoiceSpec.tenant.user.phoneNumber,
          mpesaRequestId: `REQ-${invoice.id.slice(0, 8)}-${successPaymentSequence}`,
          mpesaCheckoutRequestId: `CHK-${invoice.id.slice(0, 8)}-${successPaymentSequence}`,
          mpesaReceiptNumber: `RCP${String(100000 + successPaymentSequence)}`,
          paidAt: paymentDate,
        },
      })

      await prisma.transaction.create({
        data: {
          paymentId: payment.id,
          externalReference: `TX-${payment.id.slice(0, 10)}`,
          type: TransactionType.CALLBACK,
          provider: 'MPESA',
          resultCode: '0',
          resultDescription: 'The service request is processed successfully.',
          amount: paidAmount,
          rawPayload: {
            MerchantRequestID: payment.mpesaRequestId,
            CheckoutRequestID: payment.mpesaCheckoutRequestId,
            ResultCode: 0,
            ResultDesc: 'The service request is processed successfully.',
          },
          isValid: true,
          processedAt: paymentDate,
        },
      })

      successPaymentSequence += 1
    }
  }

  const sampleTenant = createdTenants[tenantPhone]

  await prisma.payment.create({
    data: {
      invoiceId: (
        await prisma.invoice.findFirstOrThrow({
          where: {
            tenantId: sampleTenant.id,
            status: InvoiceStatus.OVERDUE,
          },
        })
      ).id,
      tenantId: sampleTenant.id,
      userId: sampleTenant.userId,
      amount: 1000,
      method: 'MPESA',
      status: PaymentStatus.FAILED,
      phoneNumber: sampleTenant.user.phoneNumber,
      mpesaRequestId: 'REQ-FAILED-001',
      mpesaCheckoutRequestId: 'CHK-FAILED-001',
      paidAt: null,
    },
  })

  await prisma.invitation.createMany({
    data: [
      {
        code: 'INV-RIV-001',
        phoneNumber: '+254711000001',
        propertyId: riverside.id,
        unitId: unitsByNumber.B201.id,
        sentById: landlordA.id,
        status: InvitationStatus.PENDING,
        expiresAt: dateMonthsAgo(-1, 15),
        sentVia: 'SMS',
      },
      {
        code: 'INV-SUN-002',
        phoneNumber: '+254711000002',
        propertyId: sunset.id,
        unitId: unitsByNumber.S11.id,
        sentById: landlordA.id,
        status: InvitationStatus.EXPIRED,
        expiresAt: dateMonthsAgo(1, 10),
        sentVia: 'WHATSAPP',
      },
      {
        code: 'INV-GRN-003',
        phoneNumber: '+254711000003',
        propertyId: greenview.id,
        unitId: unitsByNumber.G3.id,
        sentById: landlordB.id,
        status: InvitationStatus.ACCEPTED,
        expiresAt: dateMonthsAgo(3, 18),
        acceptedAt: dateMonthsAgo(3, 12),
        sentVia: 'SMS',
      },
    ],
  })

  const maintenanceSeed = [
    {
      tenantPhone: tenantPhone,
      unitNumber: 'A101',
      assignedToId: caretakerA.id,
      title: 'Water leakage in kitchen sink',
      description: 'Leak worsens at night and has damaged cabinet base.',
      priority: MaintenancePriority.HIGH,
      status: MaintenanceStatus.IN_PROGRESS,
      createdAt: dateMonthsAgo(0, 3),
    },
    {
      tenantPhone: '+254700000011',
      unitNumber: 'A102',
      assignedToId: caretakerA.id,
      title: 'Bedroom window lock broken',
      description: 'Window does not lock completely and is a security concern.',
      priority: MaintenancePriority.MEDIUM,
      status: MaintenanceStatus.OPEN,
      createdAt: dateMonthsAgo(0, 6),
    },
    {
      tenantPhone: '+254700000012',
      unitNumber: 'S01',
      assignedToId: caretakerB.id,
      title: 'Frequent power trips',
      description: 'Main breaker trips whenever kettle and microwave are used together.',
      priority: MaintenancePriority.URGENT,
      status: MaintenanceStatus.RESOLVED,
      createdAt: dateMonthsAgo(1, 4),
      resolvedAt: dateMonthsAgo(1, 7),
    },
    {
      tenantPhone: '+254700000013',
      unitNumber: 'S02',
      assignedToId: caretakerB.id,
      title: 'Bathroom drain clog',
      description: 'Standing water after shower for over 30 minutes.',
      priority: MaintenancePriority.LOW,
      status: MaintenanceStatus.CLOSED,
      createdAt: dateMonthsAgo(2, 8),
      resolvedAt: dateMonthsAgo(2, 12),
    },
  ]

  for (const item of maintenanceSeed) {
    const tenant = createdTenants[item.tenantPhone]

    await prisma.maintenanceRequest.create({
      data: {
        tenantId: tenant.id,
        unitId: unitsByNumber[item.unitNumber].id,
        reportedById: tenant.userId,
        assignedToId: item.assignedToId,
        title: item.title,
        description: item.description,
        priority: item.priority,
        status: item.status,
        createdAt: item.createdAt,
        resolvedAt: item.resolvedAt ?? null,
      },
    })
  }

  await prisma.invoice.create({
    data: {
      tenantId: inactiveTenant.id,
      userId: inactiveTenant.userId,
      unitId: inactiveTenant.unitId,
      billingType: BillingType.RENT,
      periodMonth: now.getMonth() + 1,
      periodYear: now.getFullYear(),
      issueDate: issueDateForMonth(0),
      dueDate: dueDateForMonth(0),
      amount: 0,
      penaltyAmount: 0,
      totalAmount: 0,
      paidAmount: 0,
      status: InvoiceStatus.CANCELLED,
      paidAt: null,
    },
  })

  const userCount = await prisma.user.count()
  const propertyCount = await prisma.property.count()
  const unitCount = await prisma.unit.count()
  const tenantCount = await prisma.tenant.count()
  const invoiceCount = await prisma.invoice.count()
  const paymentCount = await prisma.payment.count()
  const maintenanceCount = await prisma.maintenanceRequest.count()

  console.log('Seed completed with rich demo data:')
  console.log({
    users: userCount,
    properties: propertyCount,
    units: unitCount,
    tenants: tenantCount,
    invoices: invoiceCount,
    payments: paymentCount,
    maintenanceRequests: maintenanceCount,
  })
}

main()
  .catch((error) => {
    console.error('Seed failed:', error)
    process.exit(1)
  })
  .finally(async () => {
    await prisma.$disconnect()
  })