const { PrismaClient, RoleName } = require('@prisma/client')
const bcrypt = require('bcryptjs')

const prisma = new PrismaClient()

async function main() {
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

  const landlordRole = await prisma.role.findUniqueOrThrow({
    where: { name: RoleName.LANDLORD },
  })

  const landlordPhone = process.env.SEED_LANDLORD_PHONE || '+254700000001'
  const landlordEmail = process.env.SEED_LANDLORD_EMAIL || 'landlord@example.com'
  const landlordPassword = process.env.SEED_LANDLORD_PASSWORD || 'Pass@1234'
  const passwordHash = await bcrypt.hash(landlordPassword, 10)

  await prisma.user.upsert({
    where: { phoneNumber: landlordPhone },
    update: {
      roleId: landlordRole.id,
      email: landlordEmail,
      firstName: 'Sample',
      lastName: 'Landlord',
      passwordHash,
      isActive: true,
    },
    create: {
      phoneNumber: landlordPhone,
      roleId: landlordRole.id,
      email: landlordEmail,
      firstName: 'Sample',
      lastName: 'Landlord',
      passwordHash,
      isActive: true,
    },
  })

  console.log('Seed completed: roles inserted and sample landlord created.')
}

main()
  .catch((error) => {
    console.error('Seed failed:', error)
    process.exit(1)
  })
  .finally(async () => {
    await prisma.$disconnect()
  })