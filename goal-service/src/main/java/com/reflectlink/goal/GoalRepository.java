package com.reflectlink.goal;

import org.springframework.data.jpa.repository.JpaRepository;
import java.util.List;

public interface GoalRepository extends JpaRepository<Goal, Long> {
    List<Goal> findByUserIdOrderByPeriodStartDesc(Integer userId);
}
