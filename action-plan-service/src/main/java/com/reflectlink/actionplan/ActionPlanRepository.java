package com.reflectlink.actionplan;

import org.springframework.data.jpa.repository.JpaRepository;
import java.util.List;

public interface ActionPlanRepository extends JpaRepository<ActionPlan, Long> {
    List<ActionPlan> findByPostIdOrderByCreatedAtDesc(Integer postId);
}
