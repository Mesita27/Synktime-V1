# Node.js/NestJS API Migration Placeholder

## Purpose

This directory is reserved for future migration of the PHP API to Node.js with NestJS framework.

## Current State

Current backend:
- PHP 7.4+ with PDO
- Procedural code with some OOP
- Direct SQL queries
- Session-based authentication

## Why NestJS?

- **TypeScript**: Type safety across the stack
- **Architecture**: Built-in DI, modular structure
- **Performance**: Non-blocking I/O for concurrent requests
- **Real-time**: Native WebSocket support
- **GraphQL**: First-class GraphQL support
- **Modern**: Decorator-based, similar to Spring/Angular

## Proposed Structure

```
/api-node-nest/
  /src/
    /auth/           # Authentication module
    /attendance/     # Attendance module
    /employees/      # Employees module
    /schedules/      # Schedules module
    /reports/        # Reports module
    /biometric/      # Biometric integration
    /common/         # Shared utilities
      /decorators/
      /guards/
      /interceptors/
      /filters/
  /test/            # E2E tests
  package.json
  tsconfig.json
  nest-cli.json
```

## Technology Stack

### Core
- **NestJS**: Web framework
- **TypeScript**: Language
- **TypeORM** or **Prisma**: ORM
- **class-validator**: DTO validation
- **class-transformer**: Object transformation

### Database
- **MySQL/MariaDB**: Same as current
- **Redis**: Session storage, caching

### Authentication
- **Passport**: Auth middleware
- **JWT**: Token-based auth
- **bcrypt**: Password hashing

### Testing
- **Jest**: Unit testing
- **Supertest**: E2E testing

### Documentation
- **Swagger/OpenAPI**: Auto-generated API docs

## API Contracts

The Node API must maintain identical contracts to PHP API:

### Authentication
```typescript
POST /api/auth/login
Body: { username: string, password: string }
Response: { success: boolean, token: string, user: User }
```

### Attendance
```typescript
GET /api/attendance?date=YYYY-MM-DD
Response: { success: boolean, data: Attendance[] }

POST /api/attendance
Body: { employeeId: number, type: 'ENTRADA' | 'SALIDA' }
Response: { success: boolean, data: Attendance }
```

### Employees
```typescript
GET /api/employees
Response: { success: boolean, data: Employee[] }

POST /api/employees
Body: CreateEmployeeDto
Response: { success: boolean, data: Employee }
```

## Migration Strategy

### Phase 1: Setup (Week 1-2)
- [ ] Initialize NestJS project
- [ ] Configure TypeORM/Prisma
- [ ] Setup database connection
- [ ] Configure authentication
- [ ] Setup testing infrastructure

### Phase 2: Core Modules (Week 3-6)
- [ ] Auth module (login, logout, guards)
- [ ] Employees module (CRUD)
- [ ] Schedules module (CRUD)
- [ ] Attendance module (register, list)

### Phase 3: Advanced Features (Week 7-9)
- [ ] Reports module
- [ ] Biometric integration
- [ ] Real-time updates (WebSocket)
- [ ] File uploads
- [ ] Notifications

### Phase 4: Testing & Documentation (Week 10-11)
- [ ] Unit tests for all services
- [ ] E2E tests for all endpoints
- [ ] Swagger documentation
- [ ] Performance testing

### Phase 5: Deployment (Week 12-13)
- [ ] Docker configuration
- [ ] CI/CD pipeline
- [ ] Load testing
- [ ] Production deployment strategy

## Example Module Structure

```typescript
// employees.module.ts
@Module({
  imports: [TypeOrmModule.forFeature([Employee])],
  controllers: [EmployeesController],
  providers: [EmployeesService],
  exports: [EmployeesService]
})
export class EmployeesModule {}

// employees.controller.ts
@Controller('api/employees')
@UseGuards(JwtAuthGuard)
export class EmployeesController {
  constructor(private employeesService: EmployeesService) {}

  @Get()
  async findAll(): Promise<ResponseDto<Employee[]>> {
    const data = await this.employeesService.findAll();
    return { success: true, data };
  }

  @Post()
  async create(@Body() dto: CreateEmployeeDto): Promise<ResponseDto<Employee>> {
    const data = await this.employeesService.create(dto);
    return { success: true, data };
  }
}

// employees.service.ts
@Injectable()
export class EmployeesService {
  constructor(
    @InjectRepository(Employee)
    private employeesRepository: Repository<Employee>
  ) {}

  async findAll(): Promise<Employee[]> {
    return this.employeesRepository.find();
  }

  async create(dto: CreateEmployeeDto): Promise<Employee> {
    const employee = this.employeesRepository.create(dto);
    return this.employeesRepository.save(employee);
  }
}
```

## Environment Variables

```env
NODE_ENV=development
PORT=3000

# Database
DB_HOST=localhost
DB_PORT=3306
DB_NAME=synktime
DB_USER=root
DB_PASSWORD=secret

# Auth
JWT_SECRET=your-secret-key
JWT_EXPIRATION=7d

# Services
ML_SERVICE_URL=http://localhost:8000

# Redis
REDIS_HOST=localhost
REDIS_PORT=6379
```

## Performance Considerations

### Advantages of Node.js
- Non-blocking I/O: Better for concurrent requests
- WebSocket: Native real-time support
- JSON: Faster JSON parsing than PHP
- Ecosystem: Modern tooling

### Potential Challenges
- Memory: Higher baseline memory usage
- CPU: Not ideal for CPU-intensive tasks
- Learning Curve: Team needs Node.js/TypeScript expertise

## Decision Criteria

Migrate to Node.js/NestJS when:
- [ ] Need for real-time features (WebSocket)
- [ ] Team has TypeScript/Node.js expertise
- [ ] GraphQL API required
- [ ] Better async performance needed
- [ ] Microservices architecture planned
- [ ] Resources available for 3-month migration

## Coexistence Strategy

During migration:
1. Both APIs run simultaneously
2. Nginx routes to appropriate service
3. New features in Node, maintain PHP
4. Gradual migration of endpoints
5. Shared database

## Getting Started (Future)

```bash
cd services/api-node-nest
npm install
npm run start:dev
```

Visit `http://localhost:3000/api/docs` for Swagger documentation.

## References

- [NestJS Documentation](https://docs.nestjs.com/)
- [TypeORM](https://typeorm.io/)
- [Prisma](https://www.prisma.io/)
- [Passport.js](http://www.passportjs.org/)

## Notes

This is a placeholder for future work. The Node.js migration is optional and should only proceed if there are clear business or technical benefits over the PHP implementation.
